"""Remove the repo's Markdown notes from the public webroot.

The deploy skips hidden files, so the server keeps its own .htaccess, which does
not refuse .md. These notes are internal; this removes exactly the .md paths that
are tracked in the repo, and nothing else. See docs/deploy.md.
"""

import os
import posixpath
import sys

import paramiko

WEBROOT = "webroots/www"


def main():
    with open(sys.argv[1], encoding="utf-8") as listing:
        paths = [line.strip() for line in listing if line.strip().endswith(".md")]

    client = paramiko.SSHClient()
    # Match the existing deployment's host-key policy; secrets stay in the runner.
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        os.environ["SFTP_HOST"], username=os.environ["SFTP_USER"],
        password=os.environ["SFTP_PASS"], timeout=30,
        allow_agent=False, look_for_keys=False,
    )
    try:
        sftp = client.open_sftp()
        for relative in paths:
            if relative.startswith("/") or ".." in relative.split("/"):
                print(f"skipped unsafe path {relative}")
                continue
            remote = posixpath.join(WEBROOT, relative)
            try:
                sftp.remove(remote)
                print(f"removed {remote}")
            except FileNotFoundError:
                print(f"not present {remote}")
    finally:
        client.close()


if __name__ == "__main__":
    main()
