# history.md — sebastienvanblaere.be

Append-only journal van inhoudelijke sessie-takeaways. Wordt door de
SessionStart-hook (`cloud-md-loader.sh`) ingelezen — laatste 50 regels.

---

## 2026-05-09 — projectenindex + zandbak-opruim

- 5 STARTER's + index uit zip blijken al canoniek te wonen in vault
  `C:\server_documents\Memory\Memory\98_AI_Context\00_access\`
- Cherry-pick `a972626` op master: linktree-link voor `p5.waves_lab (service)`
  (was 3 dagen blijven hangen in worktree `epic-chatelet-66ce06`)
- 3 stale worktrees opgeruimd (`epic-chatelet`, `dreamy-gates`, `reverent-curran`)
- Gepusht naar `seb-prjcts-be/sebastienvanblaere_webinterface`

## 2026-05-09 — GitHub Actions auto-deploy gefixt

- Secrets ingesteld via `gh secret set` (`SFTP_HOST`, `SFTP_USER`, `SFTP_PASS`)
- `remote_path` in `deploy.yml` was dubbel: `sebastienvanblaere.be/httpd.www/`
  → SSH-home zit al in domain-folder → `'./'` werkt
- Eerste succesvolle deploy sinds setup. ~14 sec push-naar-live
- Drie dagen aan eerdere commits (mobile-CSS, menu, schrijfsel-regel)
  zijn door deze deploy mee live geraakt
- `.htaccess`: `*.md` block toegevoegd zodat cloud.md/MEMORY.md/STARTERs
  niet publiek staan

## 2026-05-09 — services-pattern onderzocht

- Ground-truth check: `services/p5.blocks/`, `services/p5.export/`,
  `services/creative_coding_site/` zijn **spookkopieën**, niet halfafgeronde
  migraties. Folder-mtime (2026-05-03) is misleidend — file-mtimes zijn
  2026-03-26 (oudere snapshot).
- Cursus-site: 108/109 drift-files in **root** recenter dan hub-copy.
  Root (`htdocs/p5_cursus_site/`) is canonical, hub-copy mag opgeruimd.
- Nieuwe regel: `services/<naam>/` alleen voor hub-gekoppelde diensten
  (zoals `fidk`). Libraries en content-sites met eigen subdomein wonen
  in `htdocs/<naam>/` root.
- Vastgelegd in `~/.claude/projects/.../memory/feedback_services_pattern.md`

## 2026-05-10 — Claude Code hooks (machine-globaal, niet repo-specifiek)

- `~/.claude/hooks/cloud-md-loader.sh` — laadt `cloud.md` + `history.md`
  bij sessiestart (was python3-broken, nu via node)
- `~/.claude/hooks/git-sync.sh` — auto-pull op master/main, auto-merge
  master in `claude/*` worktrees (fast-forward only)
- Settings.json clean: hooks roepen scripts aan, geen inline-escape-hel meer
- Backup: `~/.claude/settings.backup-2026-05-10.json`

## Open beslissingen voor volgende sessies

- **Cleanup `services/` spookkopieën:** `p5.blocks/`, `p5.export/`,
  `creative_coding_site/` (eerst 9 hub-unique files cherry-picken naar root)
- **`services/svg/`:** eigen GitHub-backup nog niet ingesteld
- **`AGENTS.md` in `p5_blocks/`:** rename naar `CLAUDE.md` voor
  consistentie met Claude Code-conventie (auto-load)
- **Daily_p5.waves**: lokaal-only, repo aanmaken indien gewenst
- **Even Care/Rust:** lokale folders niet in `htdocs` — Android Studio path
  documenteren

## Werkafspraken (vastgesteld)

- `services/` ≠ "alle eigen libraries onder de hub". Alleen hub-gekoppeld.
- Auto-deploy is master-on-push. Voor ad-hoc fixes: commit op master,
  push, ~14 sec live.
- Hooks doen project-context laden + master-sync. Geen STARTER-paste meer
  nodig in Claude Code voor dagelijks werk.
- Eind van inhoudelijke sessie: vraag AI om `history.md`-append, commit, sluit.
