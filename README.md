phpADNSite
==========

Personal website to display and archive app.net posts.

## What is phpADNSite?

phpADNSite is an open source, personal website software that serves as an extension of the app.net social network (ADN), providing you with the following features:
* Archival of all your public posts into a relational database under your own control
* Presentation of all your public posts as a stream or single post pages with permalinks on your own domain and with your custom design template -> your personal microblog or tumble log powered by app.net

## What isn't phpADNSite?

It is not a full app.net client, not a publishing tool and not an aggregator for multiple social network accounts. You can browse and post to app.net with any client you want and your posts are subsequently retrieved and stored on your own site. This concept is also known as [PESOS - Publish Elsewhere, Syndicate on your Own Site](http://indiewebcamp.com/PESOS).

## What do I need to run phpADNSite?

phpADNSite is built in PHP using state-of-the-art libraries such as silex (framework), Doctrine (ORM), Guzzle (HTTP client) and Twig (template engine). You need a domain, a web server capable of running PHP and URL rewriting as well as a MySQL database (through Doctrine it may be possible to use other DBMS as well).

## Who is behind it and what is the status of the project?

The project is currently in development and maintained by Lukas Rosenstock. I am @lukasros on app.net and [run my own site](http://lukasrosenstock.net) on phpADNSite. The software, however, is not yet considered production ready for everyone because there is no on boarding process and lack of documentation. If you are interested in using or even contributing, let me know!