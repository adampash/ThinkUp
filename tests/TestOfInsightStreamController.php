<?php
/**
 *
 * ThinkUp/tests/TestOfInsightStreamController.php
 *
 * Copyright (c) 2013 Gina Trapani
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkup.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 *
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2013 Gina Trapani
 */
require_once dirname(__FILE__).'/init.tests.php';
require_once THINKUP_WEBAPP_PATH.'_lib/extlib/simpletest/autorun.php';
require_once THINKUP_WEBAPP_PATH.'config.inc.php';

class TestOfInsightStreamController extends ThinkUpInsightUnitTestCase {

    public function setUp() {
        parent::setUp();
    }

    protected function buildPublicAndPrivateInsights() {
        $builders = array();

        //owner
        $salt = 'salt';
        $pwd1 = ThinkUpTestLoginHelper::hashPasswordUsingCurrentMethod('pwd3', $salt);
        $builders[] = FixtureBuilder::build('owners', array('id'=>1, 'full_name'=>'ThinkUp J. User',
        'email'=>'tuuser1@example.com', 'is_activated'=>1, 'pwd'=>$pwd1, 'pwd_salt'=>OwnerMySQLDAO::$default_salt));

        $builders[] = FixtureBuilder::build('owners', array('id'=>2, 'full_name'=>'ThinkUp J. User',
        'email'=>'tuuser2@example.com', 'is_activated'=>1, 'pwd'=>$pwd1, 'pwd_salt'=>OwnerMySQLDAO::$default_salt));

        //public instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>1, 'network_user_id'=>'10',
        'network_username'=>'jack', 'network'=>'twitter', 'network_viewer_id'=>'10',
        'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>1, 'posts_per_day'=>11,
        'posts_per_week'=>77));
        //private instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>2, 'network_user_id'=>'11',
        'network_username'=>'jill', 'network'=>'twitter', 'network_viewer_id'=>10,
        'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>0));
        //another public instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>3, 'network_user_id'=>'12',
        'network_username'=>'mary', 'network'=>'twitter', 'network_viewer_id'=>'10',
        'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>1, 'posts_per_day'=>11,
        'posts_per_week'=>77));
        // Facebook public instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>4, 'network_user_id'=>'13',
        'network_username'=>'Bill Cõsby', 'network'=>'facebook', 'network_viewer_id'=>'10',
        'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>1, 'posts_per_day'=>11,
        'posts_per_week'=>77));

        //owner instances
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 1, 'owner_id'=>1) );
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 2, 'owner_id'=>1) );
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 3, 'owner_id'=>1) );
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 4, 'owner_id'=>1) );

        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 1, 'owner_id'=>2) );

        //public insights
        $time_now = date("Y-m-d H:i:s");
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-05-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'1', 'headline'=>'Booyah!', 'text'=>'Hey these are some local followers!',
        'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'localfollowers', 'time_generated'=>$time_now,
        'related_data'=>self::getRelatedDataListOfUsers(), 'header_image'=>'http://example.com/header_image.gif'));
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-06-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'1', 'headline'=>'Booyah!', 'text'=>'This is a list of posts!',
        'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'favoriteflashbacks', 'time_generated'=>$time_now,
        'related_data'=>self::getRelatedDataListOfPosts()));
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-05-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'3', 'headline'=>'Booyah!', 'related_data'=>null,
        'text'=>'Retweet spike! Mary\'s post publicly got retweeted 110 times',
        'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'retweetspike', 'time_generated'=>$time_now));
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-06-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'3', 'headline'=>'Booyah!',
        'text'=>'Retweet spike! Mary\'s post publicly got retweeted 110 times',
        'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'retweetspike', 'time_generated'=>$time_now,
        'related_data'=>self::getRelatedDataListOfPosts()));
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-06-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'4', 'headline'=>'Booyah Facebook!', 'text'=>'This is Bill Cõsby\'s Facebook post!',
        'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'retweetspike',
        'time_generated'=>$time_now, 'related_data'=>self::getRelatedDataListOfPosts('facebook')));
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-06-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'4', 'headline'=>'Biggest Facebook fans!', 'text'=>'This is a list of users!',
        'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'localfollowers', 'time_generated'=>$time_now,
        'related_data'=>self::getRelatedDataListOfUsers('facebook')));
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-06-01', 'slug'=>'favorited_links',
        'instance_id'=>'3', 'headline'=>'Favorite Links',
        'text'=>'Look at those links.',
        'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'favoritedlinks', 'time_generated'=>$time_now,
        'related_data'=>self::getRelatedDataListOfPosts('twitter',1,1)));

        //private insights
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-05-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'2', 'headline'=>'Booyah!', 'text'=>'Retweet spike! Jill\'s post privately got retweeted 110 '.
        'times', 'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'retweetspike',
        'time_generated'=>$time_now, 'related_data'=>null));
        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-06-01', 'slug'=>'avg_replies_per_week',
        'instance_id'=>'2', 'headline'=>'Booyah!', 'text'=>'Retweet spike! Jill\'s post privately got retweeted 110 '.
        'times', 'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'retweetspike',
        'time_generated'=>$time_now, 'related_data'=>null));
        return $builders;
    }

    protected function buildSinglePublicInsight() {
        $builders = array();

        //owner
        $salt = 'salt';
        $pwd1 = ThinkUpTestLoginHelper::hashPasswordUsingCurrentMethod('pwd3', $salt);
        $builders[] = FixtureBuilder::build('owners', array('id'=>1, 'full_name'=>'ThinkUp J. User',
            'email'=>'tuuser1@example.com', 'is_activated'=>1, 'pwd'=>$pwd1, 'pwd_salt'=>OwnerMySQLDAO::$default_salt));

        $builders[] = FixtureBuilder::build('owners', array('id'=>2, 'full_name'=>'ThinkUp J. User',
            'email'=>'tuuser2@example.com', 'is_activated'=>1, 'pwd'=>$pwd1, 'pwd_salt'=>OwnerMySQLDAO::$default_salt));

        //public instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>1, 'network_user_id'=>'10',
            'network_username'=>'jack', 'network'=>'twitter', 'network_viewer_id'=>'10',
            'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>1, 'posts_per_day'=>11,
            'posts_per_week'=>77));
        //private instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>2, 'network_user_id'=>'11',
            'network_username'=>'jill', 'network'=>'twitter', 'network_viewer_id'=>10,
            'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>0));
        //another public instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>3, 'network_user_id'=>'12',
            'network_username'=>'mary', 'network'=>'twitter', 'network_viewer_id'=>'10',
            'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>1, 'posts_per_day'=>11,
            'posts_per_week'=>77));
        // Facebook public instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>4, 'network_user_id'=>'13',
            'network_username'=>'Bill Cõsby', 'network'=>'facebook', 'network_viewer_id'=>'10',
            'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>1, 'posts_per_day'=>11,
            'posts_per_week'=>77));

        //owner instances
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 1, 'owner_id'=>1) );
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 2, 'owner_id'=>1) );
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 3, 'owner_id'=>1) );
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 4, 'owner_id'=>1) );

        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 1, 'owner_id'=>2) );

        //public insights
        $time_now = date("Y-m-d H:i:s");

        $builders[] = FixtureBuilder::build('insights', array('date'=>'2012-06-01', 'slug'=>'avg_replies_per_week',
            'instance_id'=>'4', 'headline'=>'Biggest Facebook fans!', 'text'=>'This is a list of users!',
            'emphasis'=>Insight::EMPHASIS_HIGH, 'filename'=>'localfollowers', 'time_generated'=>$time_now,
            'related_data'=>self::getRelatedDataListOfUsers('facebook')));

        return $builders;
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testController() {
        $controller = new InsightStreamController();
        $this->assertIsA($controller, 'InsightStreamController');
    }

    public function testOfNotLoggedInNoInsights() {
        $controller = new InsightStreamController();
        $results = $controller->go();
        $this->assertPattern('/Log in/', $results);
        $this->assertPattern('/Email/', $results);
        $this->assertPattern('/Password/', $results);
    }

    public function testOfNotLoggedInInsights() {
        $builders = self::buildPublicAndPrivateInsights();

        $controller = new InsightStreamController();
        $results = $controller->go();

        //don't show login screen
        $this->assertNoPattern('/Email/', $results);
        $this->assertNoPattern('/Password/', $results);
        //do show public insights
        $this->assertPattern('/Hey these are some local followers!/', $results);
        //don't show private insights
        $this->assertNoPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);
        // Logo should not link to homepage for OSP users
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
    }

    public function testOfNotLoggedInSingleInsightInStream() {
        $builders = self::buildSinglePublicInsight();

        $controller = new InsightStreamController();
        $results = $controller->go();

        $this->debug($results);

        // Don't show login screen
        $this->assertNoPattern('/Email/', $results);
        $this->assertNoPattern('/Password/', $results);
        // Do show public insights
        $this->assertPattern('/This is a list of users!/', $results);
        // Logo should not link to homepage for OSP users
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
        // Tout shouldn't show up in the stream, only on permalink
        $this->assertNoPattern('/See which new friends you/', $results);
    }

    public function testOfLoggedInInsightsOwnsPrivateInstance() {
        $builders = self::buildPublicAndPrivateInsights();
        $this->simulateLogin('tuuser1@example.com', false);

        $controller = new InsightStreamController();
        $results = $controller->go();

        //don't show login screen
        $this->assertNoPattern('/Email/', $results);
        $this->assertNoPattern('/Password/', $results);
        //do show public insights
        $this->assertPattern('/Hey these are some local followers!/', $results);
        //do show private insights that owner owns
        $this->assertPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);
        // Logo should not link to homepage
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
    }

    public function testOfLoggedInInsightsDoesntOwnPrivateInstance() {
        $builders = self::buildPublicAndPrivateInsights();
        $this->simulateLogin('tuuser2@example.com', false);

        $controller = new InsightStreamController();
        $results = $controller->go();

        //don't show login screen
        $this->assertNoPattern('/Email/', $results);
        $this->assertNoPattern('/Password/', $results);
        //do show public insights
        $this->assertPattern('/Hey these are some local followers!/', $results);
        //don't show private insights owner doesn't own
        $this->assertNoPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);
    }

    public function testOfLoggedInInsightsOnThinkupCom() {
        $builders = self::buildPublicAndPrivateInsights();
        $this->simulateLogin('tuuser1@example.com', false);

        $cfg = Config::getInstance();
        $cfg->setValue('thinkupllc_endpoint', 'set to something');
        $controller = new InsightStreamController();
        $results = $controller->go();

        // Logo should not link to homepage
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
    }

    public function testOfLoggedOutInsightsOnThinkupCom() {
        $builders = self::buildPublicAndPrivateInsights();

        $cfg = Config::getInstance();
        $cfg->setValue('thinkupllc_endpoint', 'set to something');
        $controller = new InsightStreamController();
        $results = $controller->go();

        // Logo should not link to homepage
        $this->assertPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
    }

    public function testOfLoggedInNoServiceUsersNoInsights() {
        //set up owner
        $pwd1 = ThinkUpTestLoginHelper::hashPasswordUsingCurrentMethod('pwd3', 'salt');
        $builders[] = FixtureBuilder::build('owners', array('id'=>1, 'full_name'=>'ThinkUp J. User',
        'email'=>'tuuser1@example.com', 'is_activated'=>1, 'pwd'=>$pwd1, 'pwd_salt'=>OwnerMySQLDAO::$default_salt));

        $this->simulateLogin('tuuser1@example.com', false);

        $controller = new InsightStreamController();
        $results = $controller->go();

        $this->assertNoPattern('/Email/', $results);
        $this->assertNoPattern('/Password/', $results);
        //don't show insights
        $this->assertNoPattern('/Hey these are some local followers!/', $results);
        $this->assertNoPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);
        $this->assertPattern('/Welcome to ThinkUp/', $results);
        $this->assertPattern('/Set up a/', $results);
        $this->assertPattern('/Twitter/', $results);
        $this->assertPattern('/Foursquare/', $results);
        $this->assertPattern('/Facebook/', $results);
        $this->assertPattern('/Google/', $results);
        $this->assertPattern('/account/', $results);

        $cfg = Config::getInstance();
        $cfg->setValue('thinkupllc_endpoint', 'set to something');

        $controller = new InsightStreamController();
        $results = $controller->go();
        $this->assertNoPattern('/Welcome to ThinkUp/', $results);
        $this->assertPattern('/Watch your inbox./', $results);
        $this->assertPattern('/ThinkUp is analyzing your very first insights/', $results);
        $this->assertPattern('/On the daily./', $results);
        $this->assertNoPattern('/Set up a/', $results);
    }

    public function testOfLoggedInServiceUsersNoInsights() {
        //set up owner
        $pwd1 = ThinkUpTestLoginHelper::hashPasswordUsingCurrentMethod('pwd3', 'salt');
        $builders[] = FixtureBuilder::build('owners', array('id'=>1, 'full_name'=>'ThinkUp J. User',
        'email'=>'tuuser1@example.com', 'is_activated'=>1, 'pwd'=>$pwd1, 'pwd_salt'=>OwnerMySQLDAO::$default_salt));
        //set up instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>1, 'network_user_id'=>'10',
        'network_username'=>'jack', 'network'=>'twitter', 'network_viewer_id'=>'10',
        'crawler_last_run'=>'1988-01-20 12:00:00', 'is_active'=>1, 'is_public'=>1, 'posts_per_day'=>11,
        'posts_per_week'=>77));

        //set up owner instances
        $builders[] = FixtureBuilder::build('owner_instances', array('instance_id' => 1, 'owner_id'=>1) );

        $this->simulateLogin('tuuser1@example.com', false);

        $controller = new InsightStreamController();
        $results = $controller->go();

        $this->assertNoPattern('/Email/', $results);
        $this->assertNoPattern('/Password/', $results);
        //don't show insights
        $this->assertNoPattern('/Hey these are some local followers!/', $results);
        $this->assertNoPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);
        $this->assertNoPattern('/Set up a/', $results);
        $this->assertPattern('/Check back later/', $results);
        $this->assertPattern('/update your ThinkUp data now/', $results);
        $this->debug($results);
    }

    public function testOfLoggedInIndividualInsightWithAccess() {
        $builders = self::buildPublicAndPrivateInsights();
        $this->simulateLogin('tuuser1@example.com', false);

        $_GET['u'] = 'jill';
        $_GET['n'] = 'twitter';
        $_GET['d'] = '2012-05-01';
        $_GET['s'] = 'avg_replies_per_week';
        $controller = new InsightStreamController();
        $results = $controller->go();

        //do show owned private insight
        $this->assertPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);

        // Logo link should not go to the homepage.
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
    }

    public function testOfLoggedInIndividualInsightWithoutAccess() {
        $builders = self::buildPublicAndPrivateInsights();
        $this->simulateLogin('tuuser2@example.com', false);

        $_GET['u'] = 'jill';
        $_GET['n'] = 'twitter';
        $_GET['d'] = '2012-05-01';
        $_GET['s'] = 'avg_replies_per_week';
        $controller = new InsightStreamController();
        $results = $controller->go();

        //don't show owned private insight
        $this->assertNoPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);
        //do show no access message
        $this->assertPattern('/Log in/', $results);
        $this->assertPattern('/to see this insight/', $results);
        $this->debug($results);

        // Logo link should not go to the homepage.
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
    }

    public function testOfNotLoggedInIndividualInsightWithoutAccess() {
        $builders = self::buildPublicAndPrivateInsights();

        $_GET['u'] = 'jill';
        $_GET['n'] = 'twitter';
        $_GET['d'] = '2012-05-01';
        $_GET['s'] = 'avg_replies_per_week';
        $controller = new InsightStreamController();
        $results = $controller->go();

        //don't show owned private insight
        $this->assertNoPattern('/Retweet spike! Jill\'s post privately got retweeted 110 times/', $results);
        //do show no access message
        $this->assertPattern('/Log in/', $results);
        $this->assertPattern('/to see this insight/', $results);
        $this->debug($results);

        // Logo link should not go to the homepage.
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);

        // No sharing image
        $this->assertNoPattern('/itemprop="image"/', $results);
    }

    public function testOfNotLoggedInIndividualInsightWithAccess() {
        $builders = self::buildPublicAndPrivateInsights();

        $_GET['u'] = 'jack';
        $_GET['n'] = 'twitter';
        $_GET['d'] = '2012-05-01';
        $_GET['s'] = 'avg_replies_per_week';
        $controller = new InsightStreamController();
        $results = $controller->go();

        //do show public insight
        $this->assertPattern('/Hey these are some local followers!/', $results);
        //don't show no access message
        $this->assertNoPattern('/to see this insight/', $results);
        // Logo link should not go to the homepage.
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
        //Sharing image should be set to ThinkUp logo
        $this->assertPattern('/itemprop="image" content="https:\/\/www.thinkup.com\/join\/assets\/ico\/apple-touch'.
            '-icon-144-precomposed.png/', $results);
        //Twitter card is summary
        $this->assertPattern('/name="twitter:card" content="summary"/', $results);

        $this->debug($results);
    }

    public function testOfNotLoggedInIndividualInsightWithAccessLLCEndpointSet() {
        $config = Config::getInstance();
        $config->setValue('thinkupllc_endpoint', 'http://example.com');
        $config->setValue('install_folder', 'hosted-username');
        $builders = self::buildPublicAndPrivateInsights();

        $_GET['u'] = 'jack';
        $_GET['n'] = 'twitter';
        $_GET['d'] = '2012-05-01';
        $_GET['s'] = 'avg_replies_per_week';
        $controller = new InsightStreamController();
        $results = $controller->go();

        //do show public insight
        $this->assertPattern('/Hey these are some local followers!/', $results);
        //don't show no access message
        $this->assertNoPattern('/to see this insight/', $results);
        // Logo link should not go to the homepage.
        $this->assertNoPattern('/href="https:\/\/thinkup.com"\><strong>Think/', $results);
        //Sharing image should be set to dynamically-generated share image
        $this->assertPattern('/itemprop="image" content="https:\/\/shares.thinkup.com\/insight\?tu=hosted/', $results);
        //Twitter card is large
        $this->assertPattern('/name="twitter:card" content="summary_large_image"/', $results);

        $this->debug($results);
    }

    public function testOfTwitterAndFacebookLinksAndUsernames() {
        $builders = self::buildPublicAndPrivateInsights();
        $this->simulateLogin('tuuser2@example.com', false);
        $controller = new InsightStreamController();
        $results = $controller->go();
        $this->debug($results);
        //Assert Twitter user never links to Facebook
        $this->assertNoPattern('/twitter.com/intent/user?user_id=facebook-20/', $results);
        //Assert Facebook user never links to Twitter
        $this->assertNoPattern("/facebook.com/twitter-20/", $results);
        //Assert Twitter username is preceded by an @ sign
        $this->assertPattern("/@thinkup/", $results);
        //Assert Facebook username is not preceded by an @ sign
        $this->assertNoPattern("/@Matt Jacobs/", $results);
    }

    public function testOfHTTPSWithInsecureContent() {
        $builders = self::buildPublicAndPrivateInsights();
        $this->simulateLogin('tuuser2@example.com', false);
        $controller = new InsightStreamController();
        $results = $controller->go();
        $this->debug($results);
        //Assert script/meta/link not using http
        //For now, we're allowing non https imgs even though there is a browser warning because not all photos
        //attached to links are available via https
        $this->assertNoPattern('/(script|meta|link) (src|href)="http:/', $results);
        //Assert user avatars are not using http
        $this->assertNoPattern("/img src=\"http\:\/\/example.com\/avatar.jpg/", $results);
        //Assert post author_avatars not using http
        $this->assertNoPattern("/img src=\"http\:\/\/example.com\/yo.jpg/", $results);
        //Assert insight header image not using http
        $this->assertNoPattern("/img src=\"http:\/\/example.com\/header_image.gif/", $results);
    }

    public function testOfNetworkUsernameEncoding() {
        $builders = self::buildPublicAndPrivateInsights();
        $controller = new InsightStreamController();
        $results = $controller->go();
        $this->debug($results);
        //Assert spaces are encoded
        $this->assertPattern('/Bill\+Cõsby/', $results);
        //Assert accented characters are not encoded
        $this->assertNoPattern('/Bill\+Cosby/', $results);
    }

    public function testForIconLinks() {
        $builders = self::buildPublicAndPrivateInsights();
        $controller = new InsightStreamController();
        $results = $controller->go();
        $this->debug($results);
        $this->assertPattern('/"\/\/getfavicon.appspot.com\/http%3A%2F%2Ft.co%2FEuiv1aMgVD\?defaulticon=lightpng"/',
            $results);
        $this->assertPattern('/http:\/\/t.co\/Euiv1aMgVD/', $results);
        $this->assertPattern('/src="\/\/getfavicon.appspot.com\/http%3A%2F%2Fwww.kickstarter.com%2Fprojects%2F'.
            'zefrank%2Fa-show-with-ze-frank\?defaulticon=lightpng/', $results);
        $this->assertPattern('/href="http:\/\/t.co\/tFdZbL4Y">/', $results);
        $this->assertPattern('/ A Show with Ze Frank by Ze Frank — Kickstarter/', $results);
        $this->assertPattern('/ Posted by <a href="https:\/\/twitter.com\/intent\/user\?screen_name=thinkup'.
            '">@thinkup<\/a>/', $results);
    }
}
