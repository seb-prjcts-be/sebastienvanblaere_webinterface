"""Publish only the camera app to the verified webroot; never delete remote files."""

import hashlib
import os
from pathlib import Path
import posixpath
import stat
import time
from urllib.request import Request, urlopen

import paramiko

APP = "apps/a-shot-in-the-dark"
ORIGIN = "https://sebastienvanblaere.be/"


def public_bytes(relative):
    request = Request(
        ORIGIN + relative + "?verify=" + str(time.time_ns()),
        headers={"Cache-Control": "no-cache", "User-Agent": "App-publication-check"},
    )
    with urlopen(request, timeout=30) as response:
        return response.read()


def digest(content):
    return hashlib.sha256(content).hexdigest()


def ensure_directory(sftp, remote):
    try:
        info = sftp.stat(remote)
    except FileNotFoundError:
        sftp.mkdir(remote)
        return
    if not stat.S_ISDIR(info.st_mode):
        raise RuntimeError("A destination directory is occupied by a file")


def main():
    live_hash = digest(public_bytes(APP + "/index.html"))
    client = paramiko.SSHClient()
    # Match the existing deployment's host-key policy; secrets stay in the runner.
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        os.environ["SFTP_HOST"], username=os.environ["SFTP_USER"],
        password=os.environ["SFTP_PASS"], timeout=30,
        allow_agent=False, look_for_keys=False,
    )
    try:
        with client.open_sftp() as sftp:
            candidates = [".", "httpd.www", "public_html", "www", "sebastienvanblaere.be"]
            root_directories = []
            for entry in sftp.listdir_attr("."):
                try:
                    if stat.S_ISDIR(sftp.stat(entry.filename).st_mode):
                        root_directories.append(entry.filename)
                except FileNotFoundError:
                    continue
            print("SFTP root directories: " + ", ".join(sorted(root_directories)))
            candidates.extend(root_directories)
            try:
                candidates.extend(
                    "webroots/" + entry.filename
                    for entry in sftp.listdir_attr("webroots")
                    if stat.S_ISDIR(sftp.stat("webroots/" + entry.filename).st_mode)
                )
            except FileNotFoundError:
                pass
            matches = []
            for root in sorted(set(candidates)):
                try:
                    with sftp.open(posixpath.join(root, APP, "index.html"), "rb") as source:
                        remote_hash = digest(source.read())
                        print(f"App index found at {root}; matches public page: {remote_hash == live_hash}")
                        if remote_hash == live_hash:
                            matches.append(sftp.normalize(root))
                except FileNotFoundError:
                    continue
            matches = sorted(set(matches))
            if len(matches) != 1:
                raise RuntimeError(
                    f"Expected one webroot matching the public app page, found {len(matches)}; no files uploaded"
                )
            root = matches[0]
            print("Verified public app webroot by matching its existing index.html")
            target = posixpath.join(root, APP)
            # APP already exists in the verified webroot. Only create its children.
            for source in sorted(Path(APP).rglob("*")):
                relative = source.relative_to(Path(APP)).as_posix()
                destination = posixpath.join(target, relative)
                if source.is_dir():
                    ensure_directory(sftp, destination)
                elif source.is_file():
                    sftp.put(str(source), destination)
            print("Uploaded app pages, privacy documentation and downloads; no remote files removed")
    finally:
        client.close()

    expected = {name: digest(Path(APP, name).read_bytes()) for name in (
        "index.html", "privacy.html", "text-nl.html", "text-pt.html"
    )}
    for attempt in range(6):
        try:
            if all(digest(public_bytes(APP + "/" + name)) == value for name, value in expected.items()):
                print("Public pages match the committed source")
                return
        except Exception:
            if attempt == 5:
                raise
        if attempt < 5:
            time.sleep(10)
    raise RuntimeError("Upload finished, but public page hashes do not match the source")


if __name__ == "__main__":
    main()
