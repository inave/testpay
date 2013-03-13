<?php

/**
 * This sample app is provided to kickstart your experience using Facebook's
 * resources for developers.  This sample app provides examples of several
 * key concepts, including authentication, the Graph API, and FQL (Facebook
 * Query Language). Please visit the docs at 'developers.facebook.com/docs'
 * to learn more about the resources available to you
 */

// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}

// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');


/*****************************************************************************
 *
 * The content below provides examples of how to fetch Facebook data using the
 * Graph API and FQL.  It uses the helper functions defined in 'utils.php' to
 * do so.  You should change this section so that it prepares all of the
 * information that you want to display to the user.
 *
 ****************************************************************************/

require_once('sdk/src/facebook.php');

$facebook = new Facebook(array(
  'appId'  => AppInfo::appID(),
  'secret' => AppInfo::appSecret(),
  'sharedSession' => true,
  'trustForwarded' => true,
));

$user_id = $facebook->getUser();
if ($user_id) {
  try {
    // Fetch the viewer's basic information
    $basic = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    // If the call fails we check if we still have a user. The user will be
    // cleared if the error is because of an invalid accesstoken
    if (!$facebook->getUser()) {
      header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
      exit();
    }
  }

  // This fetches some things that you like . 'limit=*" only returns * values.
  // To see the format of the data you are retrieving, use the "Graph API
  // Explorer" which is at https://developers.facebook.com/tools/explorer/
  $likes = idx($facebook->api('/me/likes?limit=4'), 'data', array());

  // This fetches 4 of your friends.
  $friends = idx($facebook->api('/me/friends?limit=4'), 'data', array());

  // And this returns 16 of your photos.
  $photos = idx($facebook->api('/me/photos?limit=16'), 'data', array());

  // Here is an example of a FQL call that fetches all of your friends that are
  // using this app
  $app_using_friends = $facebook->api(array(
    'method' => 'fql.query',
    'query' => 'SELECT uid, name FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1'
  ));
}

// Fetch the basic info of the app that they are using
$app_info = $facebook->api('/'. AppInfo::appID());

$app_name = idx($app_info, 'name', '');

?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />

    <title><?php echo he($app_name); ?></title>
    <link rel="stylesheet" href="stylesheets/screen.css" media="Screen" type="text/css" />
    <link rel="stylesheet" href="stylesheets/mobile.css" media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" type="text/css" />

    <!--[if IEMobile]>
    <link rel="stylesheet" href="mobile.css" media="screen" type="text/css"  />
    <![endif]-->

    <!-- These are Open Graph tags.  They add meta data to your  -->
    <!-- site that facebook uses when your content is shared     -->
    <!-- over facebook.  You should fill these tags in with      -->
    <!-- your data.  To learn more about Open Graph, visit       -->
    <!-- 'https://developers.facebook.com/docs/opengraph/'       -->
    <meta property="og:title" content="<?php echo he($app_name); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo AppInfo::getUrl(); ?>" />
    <meta property="og:image" content="<?php echo AppInfo::getUrl('/logo.png'); ?>" />
    <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
    <meta property="og:description" content="My first app" />
    <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />

    <script type="text/javascript" src="/javascript/jquery-1.7.1.min.js"></script>

    <script type="text/javascript">
      function logResponse(response) {
        if (console && console.log) {
          console.log('The response was', response);
        }
      }

      $(function(){
        // Set up so we handle click on the buttons
        $('#postToWall').click(function() {
          FB.ui(
            {
              method : 'feed',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendToFriends').click(function() {
          FB.ui(
            {
              method : 'send',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendRequest').click(function() {
          FB.ui(
            {
              method  : 'apprequests',
              message : $(this).attr('data-message')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });
      });
    </script>

    <!--[if IE]>
      <script type="text/javascript">
        var tags = ['header', 'section'];
        while(tags.length)
          document.createElement(tags.pop());
      </script>
    <![endif]-->
  </head>
  <body>
    <div id="fb-root"></div>
    <script type="text/javascript">
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '<?php echo AppInfo::appID(); ?>', // App ID
          channelUrl : '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html', // Channel File
          status     : true, // check login status
          cookie     : true, // enable cookies to allow the server to access the session
          xfbml      : true // parse XFBML
        });

        // Listen to the auth.login which will be called when the user logs in
        // using the Login button
        FB.Event.subscribe('auth.login', function(response) {
          // We want to reload the page now so PHP can read the cookie that the
          // Javascript SDK sat. But we don't want to use
          // window.location.reload() because if this is in a canvas there was a
          // post made to this page and a reload will trigger a message to the
          // user asking if they want to send data again.
          window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
      };

      // Load the SDK Asynchronously
      (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));
    </script>
<<<<<<< HEAD
<form method="post" id="ismForm" name="ismForm" action="https://www.paypal.com/il/cgi-bin/webscr?SESSION=JiQ5eyAfNtWF1RKgPT8T7G20_uCVoFq9yh06LIRW8bV9eZZKfpTGL69tBf8&amp;dispatch=5885d80a13c0db1f8e263663d3faee8dd75b1e1ec3ad97b7af62835dd81d5d52" class=""><input type="hidden" id="CONTEXT_CGI_VAR" name="CONTEXT" value="X3-7SZn2ExXucINxlliZ_05NdFsrIIpaV9TcRYNLL_GiOwm9XgEZzWKQeV0"><input type="hidden" name="cmd" value="_flow"><input type="hidden" name="sender_email" value="itaynave@gmail.com"><input type="hidden" id="currency_out" name="currency_out" value="USD"><p>You can pay for purchases and services.</p><fieldset class="primary" id="SendMoney"><legend class="accessAid">Send Money</legend><div id="sendToAutoSuggest"><p class="group"><label for="email"><span class="labelText">To <span class="optional">(Email)</span></span></label><span class="field"><input type="text" id="email" size="34" autocomplete="off" name="email" value=""></span></p><label id="recentContacts" for="recent_select"><span class="field"><select id="recent_select" name="recent_select"><option value="paypal@godaddy.com" title="paypal@godaddy.com">paypal@godaddy.com</option><option value="a1badhemicuda@aol.com" title="a1badhemicuda@aol.com">a1badhemicuda@aol.com</option><option value="info@psiloc.com" title="info@psiloc.com">info@psiloc.com</option><option value="dealextreme@gmail.com" title="dealextreme@gmail.com">dealextreme@gmail.com</option></select></span></label></div><fieldset class="multi" id="amountFieldSet"><legend class="accessAid">Amount</legend><span class="labels"><span class="label">Amount </span></span><div class="fields"><p class="group help"><label for="amount" class="accessAid"><span class="labelText">Amount:</span></label><span class="accessAid"></span><span class="field"><input type="text" id="amount" size="10" maxlength="16" name="amount" value=""></span></p><p class="group help"><label for="amount_ccode" class="accessAid"><span class="labelText">Currency:</span></label><span class="accessAid"></span><span class="field"><select id="amount_ccode" name="amount_ccode"><option value="USD" selected>USD - U.S. Dollars</option><option value="AUD">AUD - Australian Dollars</option><option value="BRL">BRL - Brazilian Reais</option><option value="GBP">GBP - British Pounds</option><option value="CAD">CAD - Canadian Dollars</option><option value="CZK">CZK - Czech Koruny</option><option value="DKK">DKK - Danish Kroner</option><option value="EUR">EUR - Euros</option><option value="HKD">HKD - Hong Kong Dollars</option><option value="HUF">HUF - Hungarian Forints</option><option value="ILS">ILS - Israeli New Shekels</option><option value="JPY">JPY - Japanese Yen</option><option value="MYR">MYR - Malaysian Ringgit</option><option value="MXN">MXN - Mexican Pesos</option><option value="TWD">TWD - New Taiwan Dollars</option><option value="NZD">NZD - New Zealand Dollars</option><option value="NOK">NOK - Norwegian Kroner</option><option value="PHP">PHP - Philippine Pesos</option><option value="PLN">PLN - Polish Zlotys</option><option value="SGD">SGD - Singapore Dollars</option><option value="SEK">SEK - Swedish Kronor</option><option value="CHF">CHF - Swiss Francs</option><option value="THB">THB - Thai Baht</option><option value="TRY">TRY - Turkish Liras</option></select></span></p></div></fieldset><span aria-live="assertive" id="currencyEquivalent" class="help"><span class="hide" id="convertedamount">ILS</span><span class="hide" id="prependCurrencyAmt"></span>(equals $<span id="conversion">0.00 USD</span>) <input type="submit" id="updateCurrency" name="updateCurrency" value="Update" class="button secondary"><a href="#" id="currencyUpdaterID" class="currencyUpdater">Update</a></span><span id="conversionInProgress" class="help">Processing conversion . . .</span><span id="conversionError" class="help">Click <strong>Continue</strong> to view the converted amount.</span><div class="noTabsNoJS" id="SMTtabs"><p id="smtPurchaseHeader">Send payment for:</p><fieldset class="group  smtPurchaseHeightEbay smtPurchaseNoHt" id="smtPurchase"><legend class="accessAid"> </legend><span class="labels"><span class="accessAid"> </span></span><div class="fields"><label for="PurchaseGoods"><input type="radio" id="PurchaseGoods" class="purchase scTrack:main:epmt:send::start:purchase:goods" checked name="payment_type" value="G">Goods</label><label for="RetireSMEI"><input type="radio" id="RetireSMEI" class="purchase scTrack:main:epmt:send::start:purchase:ebay_item" name="payment_type" value="I">eBay Items<ul class="eBayHideCont" id="smtEbayOptions"><li class="fullwidth">PayPal recommends that you use eBay Checkout to pay for these items.<p class="break"><input class="button-as-link" id="chkoutebay" type="submit" name="checkout.x" value="Checkout on eBay"></p></li></ul></label><label for="PurchaseServices"><input type="radio" id="PurchaseServices" class="purchase scTrack:main:epmt:send::start:purchase:services" name="payment_type" value="S"><span class="smtLabel">Services</span></label></div></fieldset></div><p class="buttonPara"><input type="submit" id="submit" name="submit.x" value="Continue" class="button primary"></p></fieldset><input type="hidden" name="js_check" id="js_check" value="disabled"><input name="auth" type="hidden" value="AGVt.MBOffLi1RhhkrOAD3nMUPiAR2i1Yx9Qw.p9g0GR1TEAQUfGcpMrNZ-QaaWLqFEmX59vyM09IEUlIDl4VRQ"><input name="form_charset" type="hidden" value="UTF-8"></form>
=======

>>>>>>> 3d47dea610dd9d290ccc5d08d26aba0cd90e8ba4
    <header class="clearfix">
      <?php if (isset($basic)) { ?>
      <p id="picture" style="background-image: url(https://graph.facebook.com/<?php echo he($user_id); ?>/picture?type=normal)"></p>

      <div>
        <h1>Welcome, <strong><?php echo he(idx($basic, 'name')); ?></strong></h1>
        <p class="tagline">
          This is your app
          <a href="<?php echo he(idx($app_info, 'link'));?>" target="_top"><?php echo he($app_name); ?></a>
        </p>

        <div id="share-app">
          <p>Share your app:</p>
          <ul>
            <li>
              <a href="#" class="facebook-button" id="postToWall" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="plus">Post to Wall</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button speech-bubble" id="sendToFriends" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="speech-bubble">Send Message</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button apprequests" id="sendRequest" data-message="Test this awesome app">
                <span class="apprequests">Send Requests</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <?php } else { ?>
      <div>
        <h1>Welcome</h1>
        <div class="fb-login-button" data-scope="user_likes,user_photos"></div>
      </div>
      <?php } ?>
    </header>

    <section id="get-started">
      <p>Welcome to your Facebook app, running on <span>heroku</span>!</p>
      <a href="https://devcenter.heroku.com/articles/facebook" target="_top" class="button">Learn How to Edit This App</a>
    </section>

    <?php
      if ($user_id) {
    ?>

    <section id="samples" class="clearfix">
      <h1>Examples of the Facebook Graph API</h1>

      <div class="list">
        <h3>A few of your friends</h3>
        <ul class="friends">
          <?php
            foreach ($friends as $friend) {
              // Extract the pieces of info we need from the requests above
              $id = idx($friend, 'id');
              $name = idx($friend, 'name');
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
              <?php echo he($name); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>

      <div class="list inline">
        <h3>Recent photos</h3>
        <ul class="photos">
          <?php
            $i = 0;
            foreach ($photos as $photo) {
              // Extract the pieces of info we need from the requests above
              $id = idx($photo, 'id');
              $picture = idx($photo, 'picture');
              $link = idx($photo, 'link');

              $class = ($i++ % 4 === 0) ? 'first-column' : '';
          ?>
          <li style="background-image: url(<?php echo he($picture); ?>);" class="<?php echo $class; ?>">
            <a href="<?php echo he($link); ?>" target="_top"></a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>

      <div class="list">
        <h3>Things you like</h3>
        <ul class="things">
          <?php
            foreach ($likes as $like) {
              // Extract the pieces of info we need from the requests above
              $id = idx($like, 'id');
              $item = idx($like, 'name');

              // This display's the object that the user liked as a link to
              // that object's page.
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($item); ?>">
              <?php echo he($item); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>

      <div class="list">
        <h3>Friends using this app</h3>
        <ul class="friends">
          <?php
            foreach ($app_using_friends as $auf) {
              // Extract the pieces of info we need from the requests above
              $id = idx($auf, 'uid');
              $name = idx($auf, 'name');
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
              <?php echo he($name); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>
    </section>

    <?php
      }
    ?>

    <section id="guides" class="clearfix">
      <h1>Learn More About Heroku &amp; Facebook Apps</h1>
      <ul>
        <li>
          <a href="https://www.heroku.com/?utm_source=facebook&utm_medium=app&utm_campaign=fb_integration" target="_top" class="icon heroku">Heroku</a>
          <p>Learn more about <a href="https://www.heroku.com/?utm_source=facebook&utm_medium=app&utm_campaign=fb_integration" target="_top">Heroku</a>, or read developer docs in the Heroku <a href="https://devcenter.heroku.com/" target="_top">Dev Center</a>.</p>
        </li>
        <li>
          <a href="https://developers.facebook.com/docs/guides/web/" target="_top" class="icon websites">Websites</a>
          <p>
            Drive growth and engagement on your site with
            Facebook Login and Social Plugins.
          </p>
        </li>
        <li>
          <a href="https://developers.facebook.com/docs/guides/mobile/" target="_top" class="icon mobile-apps">Mobile Apps</a>
          <p>
            Integrate with our core experience by building apps
            that operate within Facebook.
          </p>
        </li>
        <li>
          <a href="https://developers.facebook.com/docs/guides/canvas/" target="_top" class="icon apps-on-facebook">Apps on Facebook</a>
          <p>Let users find and connect to their friends in mobile apps and games.</p>
        </li>
      </ul>
    </section>
  </body>
</html>
