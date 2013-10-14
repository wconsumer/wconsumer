<?php
/**
 * Below the required keys.php file strcture and all the keys currently used in tests. You need to copy this file with
 * name "keys.php" and fill it. If you omit some of the sections then tests depending on them will be skipped. Also
 * please make sure you keep omitted sections empty at whole rather than setting their individual keys to empty.
 */
return array(
  'github' => array(
    // oAuth application credentials
    'app' => array(
      /*
      'token'   => '',
      'secret'  => '',
      */
      /*
      If you want to omit twitter app section don't do like this:
      'token' => NULL,
      'secret' => NULL,
      Rather keep whole twitter/app section an empty array/null/false. The same is true for other sections.
      */
    ),
    // oAuth user credentials. Token and secret for oAuth1 and just secret (access_token) for oAuth2.
    'user' => array(
      /*
      'token' => 'dummy',
      'secret' => '',
      'scopes' => array('gist'),
      */
    ),
    // Service account credentials used for acceptance tests
    'webuser' => array(
      /*
      'token'  => '',
      'secret' => '',
      */
    ),
  ),
  'twitter' => array(
    'app' => array(
      /*
      'token'  => '',
      'secret' => '',
      */
    ),
    'user' => array(
      /*
      'token'  => '',
      'secret' => '',
      */
    ),
    'webuser' => array(
      /*
      'token'  => '',
      'secret' => '',
      */
    ),
  ),
  'linkedin' => array(
    'app' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
    'webuser' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
  ),
  'meetup' => array(
    'app' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
    'webuser' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
  ),
  'vimeo' => array(
    'app' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
    'webuser' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
  ),
  'google' => array(
    'app' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
  ),
  'facebook' => array(
    'app' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
    'webuser' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
  ),
  'dropbox' => array(
    'app' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
  ),
  'flickr' => array(
    'app' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
    'webuser' => array(
      // 'token' => 'abc',
      // 'secret' => 'xyz',
    ),
  ),
);