<?php

/*  phpADNSite
 Copyright (C) 2015 Lukas Rosenstock

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

require_once __DIR__."/vendor/autoload.php";

$username = @$argv[1];
$accessToken = @$argv[2];
$filename = @$argv[3];

if (!isset($username) || !isset($accessToken) || !isset($filename)) {
  echo "This is a helper script to download a configuration file from App.net file storage.\n";
  echo "Usage: php download-configuration.php <username> <accessToken> <filename>\n";
  exit(-1);
}

try {
  $client = new Guzzle\Http\Client;
  $foundUrl = null;

  // Get list of files
  $files = $client->get('https://api.app.net/users/me/files', array(
    'Authorization' => 'Bearer '.$accessToken
  ))->send()->json();
  foreach ($files['data'] as $file) {
    if ($file['name']==$filename && $file['user']['username']==$username) {
      // Match for filename found
      $foundUrl = $file['url'];
      break;
    }
  }

  while (!isset($foundUrl) && $files['meta']['more']==true) {
    //Going through pagination to find older files
    $files = $client->get('https://api.app.net/users/me/files'
      .'?before_id='.$files['meta']['min_id'], array(
      'Authorization' => 'Bearer '.$accessToken
    ))->send()->json();
    foreach ($files['data'] as $file) {
      if ($file['name']==$filename && $file['user']['username']==$username) {
        // Match for filename found
        $foundUrl = $file['url'];
        break;
      }
    }
  }

  if (isset($foundUrl)) {
    echo "Downloading configuration file ...\n";
    $configurationFileContent = $client->get($foundUrl)->send()->getBody(true);
    file_put_contents(__DIR__."/config.php", $configurationFileContent);
    exit(0);

  } else {
    echo "No configuration file found!\n";
    exit(-1);
  }

} catch (\Exception $e) {
  echo "An exception has been caught: ".$e->getMessage()."\n";
  exit(-1);
}
