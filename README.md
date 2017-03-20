# phpADNSite

## Important Notice

**app.net has shut down operation and this software no longer works in live mode. However it can be used to serve a previously created archive of app.net posts. The documentation will be updated to reflect this.**

## What is this?
phpADNSite is a tool that allows you to present your microblog posts and conversations happening on the social networking backbone app.net on your own domain in your own visual style. The idea for phpADNSite as part of the app.net ecosystem is partly inspired by Tumblr, which combines a unified stream and post interface - your existing app.net client - with a fully customizable blog layout - phpADNSite.

## Connect to the Web & Preserve Your Content
phpADNSite is also a great way for app.net users to participate in the Indie Web (a PESOS in the most broad sense of the term) and to further syndicate posts to other networks such as Twitter and Facebook through RSS. As every app.net post has a permalink on your domain, you can replace phpADNSite with a static copy of your postings in case app.net goes down or you decide to leave the network and existing links won't be broken.

All posts in phpADNSite on the default themes contain semantic markup in [microformats2](http://microformats.org/wiki/microformats2) and RDFa with [schema.org](http://schema.org) and [Open Graph Protocol](http://ogp.me).

## Requirements
To run phpADNSite you need a webserver with PHP 5.3 or higher installed (including curl and multibyte support) and capable of URL rewriting. A database is *not* required unless you use plugins that require persistence (currently none).

## Getting it set up
1. Create a directory in which you want to install phpADNSite and change to that directory in a terminal.
2. Run the following command to download phpADNSite and its dependencies: `composer create-project "lukasros/phpadnsite" . dev-master`
3. Get a personal access token with the *streams* and *update_profile* permissions. You can [create your own app on app.net](https://account.app.net/developer/apps/) and generate a token or you can use [dev-lite](http://dev-lite.jonathonduerig.com) for this.
4. Copy `config.php.template` to `config.php` and edit `config.php`. Replace *example.com* in the domains array with the domain you want to use and enter your username (without @) into the *username* field and the access token you generated in the previous step into the *access_token* field.
5. Upload the source code to any webserver. If you use an older version of Apache you may have to replace `.htaccess` with `.htaccess.alt` and if you use a different webserver you have to configure URL rewriting manually.
6. Open the URL to your webserver in your browser. You should see your latest posts. Congratulations!

Alternatively, you can also use the [phpADNSite Docker container](https://registry.hub.docker.com/u/lukasros/phpadnsite/).

## Federation
When phpADNSite shows other users, e.g. because they reposted/starred your posts or you reposted their posts, there is a link to their profile which points to the app.net Alpha microblogging client by default. However if the user is also running phpADNSite (or a compatible software) the links can go directly to their personal site on their domain! This is called app.net federation and can be easily set up:

1. Verify the setup is complete and `http://yourdomain/` shows your recent posts. Go ahead only if *yourdomain* is a public domain on a public-facing server.
2. Open the following URL: `http://yourdomain/setup/federation`. You should see this message: *The user profile has now been configured to use the domain <yourdomain> for app.net federation.*
3. There is no 3rd step. That was easy, wasn't it?!

## Implementation Details
The implementation is basically a thin layer between the app.net API and templates written in Twig together with a plugin system of PHP classes that allows pre-processing of posts, e.g. using annotations. It's based on the Silex framework. All content is served from the app.net API, there is no database of other persistence layer. You can run one instance for multiple users by mapping different (sub)domains.

Federation works like this: phpADNSite adds a custom annotation to your profile on app.net that points to your domain. While processing posts and users for display phpADNSite reads that annotation and automatically renders the right links.

You are welcome to extend phpADNSite with your own plugins and templates.

## Who's behind it?
phpADNSite was created by Lukas Rosenstock who goes by **@lukasros** on app.net and runs a copy of phpADNSite for his own microblog at [lukasrosenstock.net](http://lukasrosenstock.net/). Check out the list of [contributors](https://github.com/LukasRos/phpADNSite/graphs/contributors).

If you want to say thanks, why not flattr me? :)

[![Flattr this](https://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/thing/3725248/LukasRosphpADNSite-on-GitHub)

## Terms
This software is released under the AGPL - see LICENSE file for details.
