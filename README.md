# probono-spark-wordpress

Local WordPress development environment for the Spark the Journey (Capital Partners for Education) pro bono project.

Reference: https://ubuntu.com/tutorials/install-and-configure-wordpress#1-overview

---

# Setup

## 1. Download WordPress
Download wordpress zip:
https://wordpress.org/latest.zip

Place the zip file in this directory. Update `Dockerfile` with the correct zip file name if it doesn't match.

## 2. Artifactory
If you have not done so, follow steps 1, 2, and 3 of Initial Setup:
https://devnavhub.cloud.capitalone.com/docs/default/component/ci636034135/enforcing-authentication-in-artifactory-local-development-macos/?page_referrer=devportal#initial-setup

Log into artifactory for the `artifactory-edge-staging.cloud.capitalone.com` hostname:
```bash
echo "$ARTIFACTORY_IDENTITY_TOKEN" | docker login artifactory-edge-staging.cloud.capitalone.com \
  --username "$ARTIFACTORY_USERNAME" --password-stdin
```

Troubleshooting:
https://devnavhub.cloud.capitalone.com/docs/default/component/ci636034135/enforcing-authentication-in-artifactory-local-development-macos/?page_referrer=devportal#docker

## 3. WordPress secret keys
Go to https://api.wordpress.org/secret-key/1.1/salt/ and store the result in the file `wp_config_values.txt`.

## 4. Historical images
Images from the Spark sandbox (2013, 2020, 2025) are not tracked in git due to size.
Download the zips from Google Drive and unzip each into `wp-content/uploads/`:

https://drive.google.com/drive/folders/1_npFx5ZF7nLZfGqAO1XKHP9S_Au9Ndgq?usp=sharing

```
wp-content/uploads/
├── 2013/   ← unzip 2013.zip here
├── 2020/   ← unzip 2020.zip here
└── 2025/   ← unzip 2025.zip here
```

Images added in 2026 and beyond are tracked in git — no manual download needed for those.

## 5. Build and run
```bash
bash run.sh
```

## 6. Navigate to the site
- Site: http://localhost:9090/
- Admin: http://localhost:9090/wp-admin

All pages, settings, menus, and media library entries are restored automatically from `seed.sql`. No WP setup wizard or Media Sync needed.

---

# Ongoing Workflow

## Making WordPress settings changes
WordPress settings (active theme, menus, plugin config) are stored in the database, not in files. After making settings changes locally, run:

```bash
bash dump-db.sh
```

Then commit `seed.sql` along with your other changes so teammates get the same settings on rebuild.

## Adding new images
New images uploaded to WordPress in 2026 are tracked in git under `wp-content/uploads/2026/`. Commit them normally.

---

# Tips

## "Briefly unavailable for scheduled maintenance. Check back in a minute."
WordPress drops a `.maintenance` file in its root during updates. If an update is interrupted, the file stays and the site gets stuck in maintenance mode indefinitely. Fix it by deleting the file:

```bash
docker exec wpdev1 rm /srv/www/wordpress/.maintenance
```

Refresh the browser — maintenance mode clears immediately, no restart needed.
