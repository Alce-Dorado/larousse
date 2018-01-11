## Installation via Composer

If you do not know what Composer is, please first read [this](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

To build locally:
- Clone this Repo
- Run:

```
composer install -v --prefer-source
```

- Point either a vhost or your own server to this directory.
- Update your hosts file with desired url.
- update AWSKEY, AWSSECRET, REDSHIFTUSER, REDSHIFTPASS and REDSHIFTENDPOINT in config.cfg with valid credentials
- enjoy.