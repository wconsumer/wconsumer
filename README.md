## Web Consumer Manager

[![Stories in Ready](https://badge.waffle.io/wconsumer/wconsumer.png)](http://waffle.io/wconsumer/wconsumer) [![Build Status](https://travis-ci.org/wconsumer/wconsumer.png)](https://travis-ci.org/wconsumer/wconsumer)

Wconsumer provides an easy way to interact with web services which require oAuth/oAuth2 authorization. It maintains
users and web services credentials on its own and provides UI for this. So all what you need is to use choosen
web service API. You don't have to worry about authentication, authorization, redirects, UI, forms, etc.

### Currently Supported Services
- GitHub
- Twitter
- LinkedIn
- Meetup
- Vimeo
- Google
- Facebook
- Dropbox
- Flickr


### Installation

1. Download Wconsumer and move it to Drupal sites/all/modules dir

1. Install Composer
`curl -s http://getcomposer.org/installer | php`

1. Install Wconsumer dependencies with Composer
`php composer.phar install --dev`

1. Enable Wconsumer and Web Consumer UI modules


### Calling API

```php
// 1. The first thing you need to do is obtaining an API object. $api is just a Guzzle\Http\Client instance.
$api = Wconsumer::$github->api();

// 2. The next and last one is calling web service API and using its results
$response = $api->get('/user')->send()->json();
echo $response['login'];
```

That would output current user's GitHub login.

If that's not looking clear then you may need to look at [Guzzle](https://github.com/guzzle/guzzle) b/c we use it
for making HTTP requests.


### Handling common exceptions

The first line from the previous example may throw exception in some cases. Sometimes you may want to handle them
in your module to do some specific actions or provide specific message to user. Below how you do this:

```php
$api = null;
try {
  $api = Wconsumer::$github->api();
}
catch (ApiUnavailable $e) {
  drupal_set_message($e->getMessage(), 'error');
  return;
}

// use $api somehow
...
```

Likely that's all what you need and that's recommended way. However below more complex example just so you know you
can handle cases precisely:

```php
$api = null;
try {
  $api = Wconsumer::$github->api();
}
catch (ServiceInactive $e) {
  return error("The GitHub service integration is currently deactivated by the website administrator");
}
catch (NoUserCredentials $e) {
  return error("Before you can see your GitHub activity you need to connect with GitHub in your profile");
}
catch (AdditionalScopesRequired $e) {
  return error("Please re-connect to GitHub in your profile to see your GitHub activity");
}

// use $api somehow
...
```

The exceptions above are all descendants of the common ApiUnavailable exception class. Here a list of all common
exceptions:

- `ApiUnavailable`. Common parent of the common exceptions.
- `ServiceInactive`. Service interaction is not possible in a moment. It's disabled by website administrator, or no service credentials like client_id/client_secret provided, or some other similar reason.
- `NoUserCredentials`. No actual user credentials stored. Most likely that means user has not yet authorized access to his foreign account.
- `AdditionalScopesRequired`. User has not [yet] granted all required permissions passed into api() method. Need to re-authorize user to request additional permissions.


### Handling HTTP errors

Also you may want to handle HTTP errors coming from Guzzle:
```php
try {
  $response = $api->get('/user')->send()->json();
}
catch (ServerErrorResponseException $e) {
  return error(...);
}
```
Please refer to [Guzzle](https://github.com/guzzle/guzzle) documentation for more details about possible errors.


### Providing oAuth/oAuth2 scopes

Your module may require some addtional oAuth scopes/permissions to work. You can achieve this by following
these two steps:

1\. Provide scopes definition callback:
```php
function mymodule_wconsumer_define_required_scopes(\Drupal\wconsumer\Service\Service $service) {
  if ($service instanceof \Drupal\wconsumer\Service\Github) {
    return array('user:email');
  }

  return NULL;
}
```

2\. Provide scopes you need when calling api() method:
```php
$scopes = mymodule_wconsumer_define_required_scopes(Wconsumer::$github);
$api = Wconsumer::$github->api(NULL, $scopes);
```

The first one is used by Wconsumer when user is about to authorize or re-authorize some web service. This way Wconsumer
can know each module requirements and can ask user for all required scopes from all installed modules.