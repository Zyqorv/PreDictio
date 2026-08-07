#!/usr/bin/env python3

import argparse
import datetime
import json
import subprocess
import sys
from pathlib import Path
import socket
import os

import yaml


if os.geteuid() == 0:
    print("ERROR: Do not run app-promote.py with sudo.")
    sys.exit(1)


ROOT = Path(__file__).parent
INVENTORY_PATH = ROOT / "inventory.yaml"
LOG_PATH = ROOT / "promotion_log.jsonl"

EXCLUDED_PATHS = {
    ".git",
    ".gitignore",
    "vendor",
    "backups",
    "promotion",
}

def load_inventory():
    with open(INVENTORY_PATH) as f:
        inv = yaml.safe_load(f)

    if not inv or "lanes" not in inv:
        raise RuntimeError("Invalid inventory.yaml")

    return inv


def log_event(**fields):
    fields["timestamp"] = datetime.datetime.now(datetime.timezone.utc).isoformat() + "Z"
    with open(LOG_PATH, "a") as f:
        f.write(json.dumps(fields) + "\n")
    print(f"[log] {fields.get('result', '?')}: {fields}")


def fail(event_fields, message):
    event_fields["result"] = "FAILED"
    event_fields["reason"] = message
    log_event(**event_fields)
    print(f"ERROR: {message}", file=sys.stderr)
    sys.exit(1)

def target_config(inv, lane):
    return inv["lanes"].get(lane)


def ssh_run(cfg, remote_cmd, check=False):
    cmd = [
        "ssh", "-i", str(Path(cfg["ssh_key_path"]).expanduser()),
        "-o", "StrictHostKeyChecking=accept-new",
        f"{cfg['user']}@{cfg['host']}",
        remote_cmd,
    ]
    return subprocess.run(cmd, capture_output=True, text=True, check=check)


def scp_to(cfg, local_path, remote_path):
    cmd = [
        "scp", "-i", str(Path(cfg["ssh_key_path"]).expanduser()),
        "-o", "StrictHostKeyChecking=accept-new",
        str(local_path),
        f"{cfg['user']}@{cfg['host']}:{remote_path}",
    ]
    return subprocess.run(cmd, capture_output=True, text=True)


def make_backup(inv, lane, release_id):
    cfg = target_config(inv, lane)

    event = {
        "action": "backup",
        "lane": lane,
        "release_id": release_id,
    }

    if cfg is None:
        fail(event, f"Unknown lane: {lane}")

    backup_id = (
        f"{release_id}_"
        f"{datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ')}"
    )

    remote_backup_path = f"{cfg['backup_dir']}/{backup_id}.tar.gz"
    staging_dir = f"/tmp/{backup_id}"

    exclude_commands = " ".join(
        f"--exclude='{item}'"
        for item in EXCLUDED_PATHS
    )

    backup_command = (
        f"mkdir -p {cfg['backup_dir']} {staging_dir} && "
        f"rsync -a {exclude_commands} "
        f"{cfg['app_dir']}/ {staging_dir}/ && "
        f"tar -czf {remote_backup_path} "
        f"-C {staging_dir} .; "
        f"status=$?; "
        f"rm -rf {staging_dir}; "
        f"exit $status"
    )

    result = ssh_run(
        cfg,
        backup_command,
        check=False,
    )

    if result.returncode != 0:
        fail(
            event,
            f"Backup failed: {result.stderr or result.stdout}",
        )

    event.update(
        result="SUCCESS",
        backup_id=backup_id,
        backup_path=remote_backup_path,
    )

    log_event(**event)

    return backup_id, remote_backup_path


def promote(args):
    
    inv = load_inventory()

    event = {
        "action": "promote",
        "source_lane": args.source,
        "target_lane": args.target,
        "release_id": args.release_id,
    }

    cfg = target_config(inv, args.target)

    if cfg is None:
        fail(event, f"unknown target lane: {args.target}")

    backup_id, backup_path = make_backup(inv, args.target, args.release_id)

    event["backup_id"] = backup_id

    app_dir = Path(cfg["app_dir"])

    if args.file:
        source_file = Path(args.file)

        if not source_file.is_absolute():
            source_file = app_dir / source_file

        source_file = source_file.resolve()

        if not source_file.exists():
            fail(event, f"file not found: {source_file}")

        try:
            relative_path = source_file.relative_to(app_dir)
        except ValueError:
            fail(
                event,
                f"single-file promotion must be inside {app_dir}",
            )

        targets = [(source_file, relative_path)]

        event["mode"] = "single-file"

    else:
        targets = []

        for path in app_dir.rglob("*"):
            if not path.is_file():
                continue

            relative = path.relative_to(app_dir)

            if any(
                excluded in relative.parts
                for excluded in EXCLUDED_PATHS
            ):
                continue

            targets.append((path, relative))

        event["mode"] = "bulk-release"
        event["files"] = [
            str(relative)
            for _, relative in targets
        ]

    if not targets:
        fail(event, "no files found to promote")

    for source, relative in targets:
        remote_path = f"{cfg['app_dir']}/{relative}"

        remote_dir = str(Path(remote_path).parent)

        mkdir = ssh_run(
            cfg,
            f"mkdir -p {remote_dir}",
            check=False,
        )

        if mkdir.returncode != 0:
            fail(
                event,
                f"failed creating remote directory: {remote_dir}",
            )

        result = scp_to(
            cfg,
            source,
            remote_path,
        )

        if result.returncode != 0:
            fail(
                event,
                f"transfer failed for {relative}: {result.stderr}",
            )

    restart_result = ssh_run(
        cfg,
        cfg["restart_script"],
        check=False,
    )

    if restart_result.returncode != 0:
        fail(
            event,
            f"restart failed: {restart_result.stderr}",
        )

    health_result = ssh_run(
        cfg,
        f"curl -fsS {cfg['health_check']}",
        check=False,
    )

    if health_result.returncode != 0:
        fail(
            event,
            f"health check failed; backup {backup_id} available",
        )

    event["result"] = "SUCCESS"
    event["targets"] = [
        str(relative)
        for _, relative in targets
    ]

    log_event(**event)

    print(
        f"Promoted {args.source} -> {args.target} "
        f"({event['mode']}). Backup: {backup_id}"
    )

def find_backup(inv, current_lane, backup_id):

    search_order = [current_lane]

    if current_lane == "dev":
        search_order.append("qa")
    elif current_lane == "qa":
        search_order.append("prod")

    for candidate in search_order:
        cfg = target_config(inv, candidate)

        if cfg is None:
            continue

        backup_path = (
            f"{cfg['backup_dir']}/{backup_id}.tar.gz"
        )

        if candidate == current_lane:
            # Local check
            result = subprocess.run(
                [
                    "test",
                    "-f",
                    backup_path,
                ],
                capture_output=True,
            )

        else:
            # Remote check
            result = ssh_run(
                cfg,
                f"test -f {backup_path}",
                check=False,
            )

        if result.returncode == 0:
            return candidate, cfg, backup_path

    return None, None, None


def rollback(args):
    inv = load_inventory()

    event = {
        "action": "rollback",
        "lane": args.lane,
        "backup_id": args.backup_id,
        "initiated_from": socket.gethostname(),
    }

    backup_lane, cfg, backup_path = find_backup(
        inv,
        args.lane,
        args.backup_id,
    )

    if cfg is None:
        fail(
            event,
            f"backup not found anywhere: {args.backup_id}",
        )

    event["restored_lane"] = backup_lane
    event["backup_path"] = backup_path

    cleanup_command = (
        f"find {cfg['app_dir']} "
        f"-mindepth 1 "
        f"\\( "
        + " -o ".join(
            f"-name {item}"
            for item in EXCLUDED_PATHS
        )
        +
        f" \\) -prune -o "
        f"-exec rm -rf {{}} +"
    )

    restore_command = (
        f"{cleanup_command} && "
        f"tar -xzf {backup_path} "
        f"-C {cfg['app_dir']}"
    )

    if backup_lane == args.lane:
        # Local rollback
        result = subprocess.run(
            restore_command,
            shell=True,
            capture_output=True,
            text=True,
        )

    else:
        # Remote rollback
        result = ssh_run(
            cfg,
            restore_command,
            check=False,
        )

    if result.returncode != 0:
        fail(
            event,
            f"restore failed: {result.stderr}",
        )

    restart_result = ssh_run(
        cfg,
        cfg["restart_script"],
        check=False,
    )

    if restart_result.returncode != 0:
        fail(
            event,
            f"restart failed: {restart_result.stderr}",
        )

    verify = ssh_run(
        cfg,
        f"curl -fsS {cfg['health_check']}",
        check=False,
    )

    if verify.returncode != 0:
        fail(
            event,
            "rollback completed but health check failed",
        )

    event["result"] = "SUCCESS"

    log_event(**event)

    print(
        f"Rolled back {backup_lane} "
        f"using backup {args.backup_id}"
    )

def get_current_lane():
    hostname = socket.gethostname().lower()

    if hostname == "app-dev":
        return "dev"
    elif hostname == "app-qa":
        return "qa"
    elif hostname == "app-prod":
        return "prod"
    else:
        raise RuntimeError(f"Unknown hostname: {hostname}")



def main():
    current_lane = get_current_lane()

    parser = argparse.ArgumentParser(description="PreDictio App VM promotion tool")
    sub = parser.add_subparsers(dest="command", required=True)

    p_promote = sub.add_parser("promote", help="Promote a file or bulk release")
    p_promote.add_argument("--release-id", required=True)
    p_promote.add_argument(
        "--file",
        help="Path to a single file to promote"
    )
    p_promote.set_defaults(func=promote)

    p_rollback = sub.add_parser("rollback", help="Restore from a backup")
    p_rollback.add_argument("--backup-id", required=True)
    p_rollback.set_defaults(func=rollback)

    args = parser.parse_args()

    if current_lane == "prod" and args.command == "promote":
        print("Production cannot initiate promotions.")
        sys.exit(1)

    args.source = current_lane

    if current_lane == "dev":
        args.target = "qa"
    elif current_lane == "qa":
        args.target = "prod"

    args.lane = current_lane
    args.func(args)


if __name__ == "__main__":
    main()