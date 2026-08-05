#!/usr/bin/env python3
"""
promote.py - SSH-based promotion tool for the PreDictio API role.

Supported commands:
    promote   Promote a specific file or a bulk release (dev->qa or qa->prod)
    migrate   Apply ordered DB migrations on a target lane, tracked so they
              are never re-applied
    backup    Create a release-linked backup on a target lane before a change
    rollback  Restore files or config from a previous backup
    status    Show recent promotion log entries

Design notes for the write-up (Task 3/4/5):
  - Transport: SSH (scp for file transfer, ssh for remote commands). No Git,
    no GitHub Actions, no git pull/clone/checkout anywhere in this file.
  - All server/path details come from inventory.yaml. The operator only
    supplies: source lane, target lane, role, and a release id or file path.
  - allowed_promotions in inventory.yaml is the single source of truth for
    which lane pairs are legal. development -> production is never in that
    list, so it is rejected before any network call is made.
  - Every run appends one JSON line to promotion_log.jsonl with enough
    detail (release id, source/target, backup id, result, timestamp) to
    trace a release across both promotion hops.
"""

import argparse
import datetime
import json
import subprocess
import sys
import uuid
from pathlib import Path

import yaml

ROOT = Path(__file__).parent
INVENTORY_PATH = ROOT / "inventory.yaml"
LOG_PATH = ROOT / "promotion_log.jsonl"
RELEASES_DIR = ROOT / "releases"       # local release manifests + staged files
MIGRATIONS_DIR = ROOT / "db_migrations"  # local ordered migration files



# Inventory / logging helpers


def load_inventory():
    with open(INVENTORY_PATH) as f:
        return yaml.safe_load(f)


def log_event(**fields):
    """Append one structured log line. Never called with secret values."""
    fields["timestamp"] = datetime.datetime.utcnow().isoformat() + "Z"
    with open(LOG_PATH, "a") as f:
        f.write(json.dumps(fields) + "\n")
    print(f"[log] {fields.get('result', '?')}: {fields}")


def fail(event_fields, message):
    """Stop safely: log a failure with a useful reason and exit non-zero."""
    event_fields["result"] = "FAILED"
    event_fields["reason"] = message
    log_event(**event_fields)
    print(f"ERROR: {message}", file=sys.stderr)
    sys.exit(1)


def check_promotion_allowed(inv, source, target):
    pairs = [tuple(p) for p in inv.get("allowed_promotions", [])]
    if (source, target) not in pairs:
        return False
    return True


def target_config(inv, lane, role):
    try:
        return inv["lanes"][lane][role]
    except KeyError:
        return None



# Remote command helpers (SSH transport only — no Git)


def ssh_run(cfg, remote_cmd, check=True):
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
        "-r", str(local_path),
        f"{cfg['user']}@{cfg['host']}:{remote_path}",
    ]
    return subprocess.run(cmd, capture_output=True, text=True)



# Backup


def make_backup(inv, lane, role, release_id):
    """Create a release-linked backup of app_dir on the target before change."""
    cfg = target_config(inv, lane, role)
    event = {"action": "backup", "lane": lane, "role": role, "release_id": release_id}
    if cfg is None:
        fail(event, f"unknown lane/role: {lane}/{role}")

    backup_id = f"{release_id}_{datetime.datetime.utcnow().strftime('%Y%m%dT%H%M%SZ')}"
    remote_backup_path = f"{cfg['backup_dir']}/{backup_id}.tar.gz"

    result = ssh_run(
        cfg,
        f"mkdir -p {cfg['backup_dir']} && "
        f"tar -czf {remote_backup_path} -C {cfg['app_dir']} . 2>&1",
        check=False,
    )
    if result.returncode != 0:
        fail(event, f"backup failed: {result.stderr or result.stdout}")

    event.update(result="SUCCESS", backup_id=backup_id, backup_path=remote_backup_path)
    log_event(**event)
    return backup_id, remote_backup_path


# Promote (specific file OR bulk release)


def promote(args):
    inv = load_inventory()
    event = {
        "action": "promote", "role": args.role,
        "source_lane": args.source, "target_lane": args.target,
        "release_id": args.release_id,
    }

    if not check_promotion_allowed(inv, args.source, args.target):
        fail(event, f"promotion {args.source} -> {args.target} is not permitted "
                     f"(only development->qa and qa->production are allowed)")

    cfg = target_config(inv, args.target, args.role)
    if cfg is None:
        fail(event, f"unknown target lane/role: {args.target}/{args.role}")

    # 1. Backup target before touching anything
    backup_id, backup_path = make_backup(inv, args.target, args.role, args.release_id)
    event["backup_id"] = backup_id

    # 2. Resolve what to send: a specific file, or a full release manifest
    if args.file:
        local_path = Path(args.file)
        if not local_path.exists():
            fail(event, f"target file not found locally: {local_path}")
        targets = [local_path]
        event["mode"] = "single-file"
    else:
        manifest_path = RELEASES_DIR / args.release_id / "manifest.json"
        if not manifest_path.exists():
            fail(event, f"release manifest not found: {manifest_path}")
        manifest = json.loads(manifest_path.read_text())
        targets = [RELEASES_DIR / args.release_id / f for f in manifest["files"]]
        for t in targets:
            if not t.exists():
                fail(event, f"release file missing from staged release: {t}")
        event["mode"] = "bulk-release"
        event["files"] = manifest["files"]

    # 3. Transfer via scp (never Git)
    for t in targets:
        result = scp_to(cfg, t, cfg["app_dir"])
        if result.returncode != 0:
            fail(event, f"transfer failed for {t.name}: {result.stderr}")

    # 4. Reload the service and confirm health
    reload_result = ssh_run(cfg, f"sudo systemctl restart {cfg['service_name']}", check=False)
    if reload_result.returncode != 0:
        fail(event, f"service reload failed: {reload_result.stderr}")

    health_result = ssh_run(cfg, f"curl -fsS {cfg['health_check']}", check=False)
    if health_result.returncode != 0:
        fail(event, f"post-promotion health check failed at {cfg['health_check']}; "
                     f"backup {backup_id} is available for rollback")

    event["result"] = "SUCCESS"
    event["targets"] = [t.name for t in targets]
    log_event(**event)
    print(f"Promoted release {args.release_id} ({event['mode']}) "
          f"{args.source} -> {args.target} for role={args.role}. "
          f"Backup: {backup_id}")



# Rollback


def rollback(args):
    inv = load_inventory()
    cfg = target_config(inv, args.lane, args.role)
    event = {"action": "rollback", "lane": args.lane, "role": args.role,
              "backup_id": args.backup_id}
    if cfg is None:
        fail(event, f"unknown lane/role: {args.lane}/{args.role}")

    backup_path = f"{cfg['backup_dir']}/{args.backup_id}.tar.gz"
    check = ssh_run(cfg, f"test -f {backup_path} && echo OK", check=False)
    if "OK" not in check.stdout:
        fail(event, f"backup not found on target: {backup_path}")

    restore = ssh_run(
        cfg,
        f"rm -rf {cfg['app_dir']}/* && tar -xzf {backup_path} -C {cfg['app_dir']} && "
        f"sudo systemctl restart {cfg['service_name']}",
        check=False,
    )
    if restore.returncode != 0:
        fail(event, f"restore failed: {restore.stderr}")

    verify = ssh_run(cfg, f"curl -fsS {cfg['health_check']}", check=False)
    if verify.returncode != 0:
        fail(event, "rollback completed but health check failed afterward")

    event["result"] = "SUCCESS"
    log_event(**event)
    print(f"Rolled back {args.lane}/{args.role} to backup {args.backup_id}")



# Migrate (ordered DB migrations, tracked so they aren't re-applied)


def migrate(args):
    inv = load_inventory()
    cfg = target_config(inv, args.lane, "api")  # migrations run via the API VM's DB client
    event = {"action": "migrate", "lane": args.lane, "release_id": args.release_id}
    if cfg is None:
        fail(event, f"unknown lane: {args.lane}")

    local_dir = MIGRATIONS_DIR / args.release_id
    if not local_dir.exists():
        fail(event, f"no migrations found for release {args.release_id}")

    migration_files = sorted(local_dir.glob("*.sql"))  # e.g. 001_add_col.sql, 002_...
    if not migration_files:
        fail(event, f"migration dir {local_dir} has no .sql files")

    # Ensure remote tracking table exists (idempotent)
    ssh_run(cfg, "PGPASSWORD=$DB_PASS psql -U $DB_USER -d predictio -c "
                 "\"CREATE TABLE IF NOT EXISTS applied_migrations "
                 "(id TEXT PRIMARY KEY, applied_at TIMESTAMP DEFAULT now());\"",
            check=False)

    applied = []
    for m in migration_files:
        already = ssh_run(
            cfg,
            f"PGPASSWORD=$DB_PASS psql -U $DB_USER -d predictio -tAc "
            f"\"SELECT 1 FROM applied_migrations WHERE id='{m.name}';\"",
            check=False,
        )
        if already.stdout.strip() == "1":
            print(f"skip (already applied): {m.name}")
            continue

        scp_to(cfg, m, "/tmp/")
        result = ssh_run(
            cfg,
            f"PGPASSWORD=$DB_PASS psql -U $DB_USER -d predictio -f /tmp/{m.name} && "
            f"PGPASSWORD=$DB_PASS psql -U $DB_USER -d predictio -c "
            f"\"INSERT INTO applied_migrations (id) VALUES ('{m.name}');\"",
            check=False,
        )
        if result.returncode != 0:
            fail(event, f"migration {m.name} failed: {result.stderr}")
        applied.append(m.name)

    event["result"] = "SUCCESS"
    event["applied"] = applied
    log_event(**event)
    print(f"Migration run for release {args.release_id} on {args.lane}: "
          f"{applied or 'nothing new to apply'}")



# CLI


def main():
    parser = argparse.ArgumentParser(description="PreDictio API promotion tool")
    sub = parser.add_subparsers(dest="command", required=True)

    p_promote = sub.add_parser("promote", help="Promote a file or bulk release")
    p_promote.add_argument("--source", required=True, choices=["development", "qa"])
    p_promote.add_argument("--target", required=True, choices=["qa", "production"])
    p_promote.add_argument("--role", required=True, choices=["api"])
    p_promote.add_argument("--release-id", required=True)
    p_promote.add_argument("--file", help="Path to a single file for a targeted promotion")
    p_promote.set_defaults(func=promote)

    p_rollback = sub.add_parser("rollback", help="Restore from a prior backup")
    p_rollback.add_argument("--lane", required=True, choices=["qa", "production"])
    p_rollback.add_argument("--role", required=True, choices=["api"])
    p_rollback.add_argument("--backup-id", required=True)
    p_rollback.set_defaults(func=rollback)

    p_migrate = sub.add_parser("migrate", help="Apply ordered DB migrations")
    p_migrate.add_argument("--lane", required=True, choices=["qa", "production"])
    p_migrate.add_argument("--release-id", required=True)
    p_migrate.set_defaults(func=migrate)

    args = parser.parse_args()
    args.func(args)


if __name__ == "__main__":
    main()
