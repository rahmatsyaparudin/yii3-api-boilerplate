"""Generate documentation.html — standalone viewer untuk semua *.md di folder ini.

Cara pakai:
    python generate-documentation.py

Membaca semua file .md di direktori ini, meng-embed kontennya ke dalam
documentation.html (via documentation.template.html), sehingga hasilnya
satu file yang bisa dibuka langsung di browser tanpa server.
"""

import json
import os
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
TEMPLATE = os.path.join(HERE, "documentation.template.html")
OUTPUT = os.path.join(HERE, "documentation.html")

# Urutan baca ditentukan lewat prefix nomor di nama file:
# NN-nama.md diurutkan numerik; README.md selalu paling atas;
# file tanpa nomor (mis. Postman collection) masuk di akhir (alfabetis).
import re

def sort_key(name: str):
    if name == "README.md":
        return (0, 0, name)
    m = re.match(r"^(\d+)-", name)
    if m:
        return (1, int(m.group(1)), name)
    return (2, 0, name)

# File di luar folder ini yang ikut di-embed (relatif ke root project)
EXTRA_DOCS = [
    "../README.md",
]


def main() -> int:
    with open(TEMPLATE, encoding="utf-8") as f:
        template = f.read()

    # Kumpulkan semua file .md: folder ini + EXTRA_DOCS dari root project
    sources = [(n, os.path.join(HERE, n)) for n in os.listdir(HERE) if n.endswith((".md", ".json"))]
    for extra in EXTRA_DOCS:
        path = os.path.normpath(os.path.join(HERE, extra))
        if os.path.exists(path):
            sources.append((os.path.basename(path), path))

    sources.sort(key=lambda s: sort_key(s[0]))

    docs = {}
    for name, path in sources:
        with open(path, encoding="utf-8") as f:
            docs[name] = f.read()

    if not docs:
        print("Tidak ada file .md ditemukan.", file=sys.stderr)
        return 1

    # Nama project dari composer.json (tanpa vendor prefix, mis. "yii3-api-boilerplate")
    project = "Dokumentasi"
    composer = os.path.join(HERE, "..", "composer.json")
    if os.path.exists(composer):
        try:
            with open(composer, encoding="utf-8") as f:
                project = json.load(f).get("name", project).split("/")[-1]
        except (json.JSONDecodeError, OSError):
            pass

    # Escape </ supaya </script> di dalam markdown tidak menutup tag script.
    payload = json.dumps(docs, ensure_ascii=False).replace("</", "<\\/")

    html = template.replace("__DOCS__", payload).replace("__PROJECT__", project)
    with open(OUTPUT, "w", encoding="utf-8") as f:
        f.write(html)

    print(f"documentation.html dibuat: {len(docs)} dokumen, {os.path.getsize(OUTPUT)} bytes")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
