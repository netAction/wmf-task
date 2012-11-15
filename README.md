WMF task
========

Wikimedia Foundation - Developer interview task (Mobile)

This is a demonstration how the donation page for Wikimedia fundraising could look like on mobile devices.

Install
-------

For installation you need a server with PHP5 and MySQL. One directory below the repository there must be a file *.my.cnf* with the following content:


	[client]
	user = wmf-task
	password = "***********"
	host = "localhost"
	database="wmf-task"

In the database a table is needed:

	CREATE TABLE `conversions` (
	  `currency` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
	  `rate` double NOT NULL,
	  PRIMARY KEY (`currency`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

The file *api.php* fetches conversion rates from remote servers or the file *testRates.xml* and offers a table of conversions via json. It accepts a parameter *callback* and returns jsonP then: *api.php?callback=functionname*

	functionname({
	  "AUD":{"rate":1.0689},
	  "CAD":{"rate":0.998901},
	  "CZK":{"rate":0.0519}
	})

*index.html* and *script/donate.js* are the website. All other Javascripts in */script* and styles in */css* are generated using *jquerymobile.com/themeroller*.


TODO
----

* complement the database table by symbols for the currencies like $ and flags if the specific currency is supported by PayPal or other gateways
* add credit card payment as soon as the undependable gateway *payments.wikimedia.org/index.php/Special:GlobalCollectGateway* works.
* language detection and translations, better wording (maybe template engine)
* guess currency depending on timezone, geo ip or something
* more content and smaller buttons on tablets and large landscape phones, automatic redirect to desktop page if indicated
* some phones only tested using crossbrowsertesting but should run on all jquery mobile devices

