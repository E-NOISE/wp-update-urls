wp-update-urls
===

Standalone script used to update all URLs in a WordPress installation. This
script can be very useful after you move your site to a new server or domain
name, as well as when deploying a local copy of a WordPress site to a 'live'
location.

## How to use this script

1. Download from
[GitHub](https://github.com/E-NOISE/wp-update-urls/raw/master/wp-update-urls.php)

2. Upload `wp-update-urls.php` to your WordPress installation directory. This is
where the wp-config.php file lives.

3. Browse the `wp-update-urls.php` script on your browser. If your site's URL is
http://yoursite.com then the URL you are looking for will be
http://yoursite.com/wp-update-urls.php . At this point you should see the
following:
![Screenshot](https://github.com/E-NOISE/wp-update-urls/raw/master/wp-update-urls.png)

4. Check that the `replace` field shows the _new_ base URL for your site. Bear
in mind that http://www.yoursite.com is not the same as http://yoursite.com.

5. Click on 'Process' to replace all occurrences of `search` with `replace` in
the database.

6. DELETE SCRIPT FROM SERVER
