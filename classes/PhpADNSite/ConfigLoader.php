<?php

/*  phpADNSite - Personal Website and Post Archive powered by app.net
 Copyright (C) 2013 Lukas Rosenstock

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace PhpADNSite;

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;

/**
 * Applies the configuration file to the application.
 */
class ConfigLoader {

	static function configure(Application $app, $config) {
		// Initialize Doctrine DBAL Service Provider
		$app->register(new DoctrineServiceProvider, array(
				"db.options" => array(
						"driver" => "pdo_mysql",
						"dbname" => $config['db.name'],
						"host" => $config['db.host'],
						"user" => $config['db.user'],
						"password" => $config['db.password']
				)
		));


		// Initialize Doctrine ORM Service Provider
		$app->register(new DoctrineOrmServiceProvider, array(
				"orm.proxies_dir" => __DIR__."/../../../proxies",
				"orm.auto_generate_proxies" => true,
				"orm.em.options" => array(
						"mappings" => array(
								array(
										"type" => "annotation",
										"namespace" => "PhpADNSite\Entities",
										"path" => __DIR__."/Entities",
								)
						),
						"metadata_cache" => "apc",
						"query_cache" => "apc",
						"result_cache" => "apc"
				)
		));
		
		$localUser = $app['orm.em']->getRepository('PhpADNSite\Entities\LocalUser')->findAll();
		if (count($localUser)==1) $app['user'] = $localUser[0];
		else $app['user'] = null;

		$app['debug'] = true;
	}

}