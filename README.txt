Welcome to phpADNSite!

phpADNSite is a tool that allows you to present your microblog posts and conversations happening on the social networking backbone app.net on your own domain in your own visual style. The idea for phpADNSite as part of the app.net ecosystem is partly inspired by Tumblr, which combines a unified stream and post interface - your existing app.net client - with a fully customizable blog - phpADNSite.

The implementation is basically a thin layer between the app.net API and templates written in Twig together with a plugin system of PHP classes that allows pre-processing of posts, e.g. using annotations. It's based on the Silex framework and requires PHP 5.3 and URL rewriting to run. All content is served from the app.net API, there is no database of other persistence layer. You can run one instance for multiple users by mapping different (sub)domains.

phpADNSite is also a great way to participate in the Indie Web (a PESOS in the most broad sense of the term) and to further syndicate posts to other networks such as Twitter and Facebook through RSS. As every app.net post has a permalink on your domain, you can replace phpADNSite with a static copy of your postings in case app.net goes down or you decide to leave the network and existing links won't be broken.

phpADNSite was created by Lukas Rosenstock who goes by @lukasros on app.net and runs a copy of phpADNSite for his own microblog at http://lukasrosenstock.net/.