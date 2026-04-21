# admin.zip review

- Archive contains 76 files including `admin/`, root PHP files, and `vendor/` libraries.
- Uploaded key browsable source files to the repo: `win.php`, `admin/index.php`.
- The archive also includes modified operational files such as `admin/issue_form.php`, `admin/issue_list.php`, `admin/prize_form.php`, and `admin/settlement_month.php`.
- Notable review point: backup files `admin/lotto_sync.fixed.php` and `admin/lotto_sync_cli.fixed.php` are included. If they are only history copies, keeping them in production source control may confuse future maintenance.
- Notable review point: committed `vendor/` libraries are present inside the archive. That is acceptable for deployment snapshots, but if this repo becomes a development repo, dependency source may be better managed through Composer plus lockfile.
- The public QR entry `win.php` now normalizes and validates mobile numbers before claim submission.
- Branch issue screen `admin/issue_form.php` includes the Saturday 20:00-21:59 issuance block rule in the archive snapshot.
