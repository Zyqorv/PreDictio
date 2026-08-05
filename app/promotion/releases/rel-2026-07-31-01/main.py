"""
PreDictio API service — minimal entry point.

Provides a /health endpoint used by the promotion tool to verify a
successful deploy after each promotion (development -> qa -> production).
RabbitMQ publisher/listener logic will be layered back in as a separate,
reviewable piece of work.
"""

from fastapi import FastAPI
import os
import socket

app = FastAPI(title="PreDictio API")


@app.get("/health")
def health():
    return {
        "status": "ok",
        "hostname": socket.gethostname(),
        "lane": os.environ.get("PREDICTIO_LANE", "unknown"),
    }


@app.get("/")
def root():
    return {"service": "predictio-api"}
