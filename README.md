# Neos Content Repository -- Standalone Example

Edit the database credentials in `App\Common::getConnection` to match yours or just use docker for the database:

```sh
docker compose up -d
```

Setup the content repository via

```sh
php 01_setup.php
```

And to run an example execute it like

```sh
php 02_create_node.php
```
