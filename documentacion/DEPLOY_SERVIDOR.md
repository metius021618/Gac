# Deploy en servidor (SiteGround)

Tras cada `git push` a `main` del repo `metius021618/Gac`, actualizar producción:

```powershell
ssh -o BatchMode=yes -o ConnectTimeout=15 -p 18765 u2553-kpdyyrivjtwb@gtxm1328.siteground.biz 'cd ~/www/new.pocoyoni.com; git pull origin main; bash scripts/sync_public_html_assets.sh; git log -1 --oneline'
```

| Dato | Valor |
|------|--------|
| Host | `gtxm1328.siteground.biz` |
| Usuario | `u2553-kpdyyrivjtwb` |
| Puerto | `18765` |
| Carpeta | `~/www/new.pocoyoni.com` |
| Remoto | `https://github.com/metius021618/Gac` |
| Assets públicos | `public/assets` → sincronizar a `public_html/assets` (document root) |
