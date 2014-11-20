<?php
/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/tests/TestOfEOYMostLinksInsight.php
 *
 * Copyright (c) 2012-2014 Gina Trapani
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
 * Test of EOYMostLinksInsight
 *
 * Test for the EOYMostLinksInsight class.
 *
 * Copyright (c) 2014 Adam Pash
 *
 * @author Adam Pash adam.pash@gmail.com
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2014 Adam Pash
 */

require_once dirname(__FILE__) . '/../../../../tests/init.tests.php';
require_once THINKUP_WEBAPP_PATH.'_lib/extlib/simpletest/autorun.php';
require_once THINKUP_WEBAPP_PATH.'_lib/extlib/simpletest/web_tester.php';
require_once THINKUP_ROOT_PATH. 'webapp/plugins/insightsgenerator/model/class.InsightPluginParent.php';
require_once THINKUP_ROOT_PATH. 'webapp/plugins/insightsgenerator/insights/eoymostlinks.php';

class TestOfEOYMostLinksInsight extends ThinkUpInsightUnitTestCase {

    public function setUp(){
        parent::setUp();
        $instance = new Instance();
        $instance->id = 10;
        $instance->network_username = 'ev';
        $instance->author_id = 7612345;
        $instance->network_user_id = 7612345;
        $instance->network = 'twitter';
        $this->instance = $instance;
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testLinkUtilities() {
        $year = Date('Y');
        $link_dao = DAOFactory::getDAO('LinkDAO');
        $user_id = $this->instance->author_id;
        $counter = 12;
        $days = 0;

        // set up most links
        while ($counter != 0) {
            $post_key = $counter + 1760;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>'twitter', 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'retweet_count_cache' => $days,
            'reply_count_cache' => $days,
            'favlike_count_cache' => $days,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'post_text'=>'Link post http://lifehacker.com/'.$counter, 'pub_date'=>$today));

            $builders[] = FixtureBuilder::build('links', array('url'=>'http://lifehacker.com/'.$counter,
            'title'=>'Link '.$counter, 'post_key'=>$post_key, 'expanded_url'=>'http://lifehacker.com/'.$counter, 'error'=>'', 'image_src'=>''));
            $counter--;
        }

        // set up fewer links
        $counter = 10;
        $days = 0;
        while ($counter != 0) {
            $post_key = $counter + 1860;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>'twitter', 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'post_text'=>'Link post http://nytimes.com/'.$counter, 'pub_date'=>$today));

            $builders[] = FixtureBuilder::build('links', array('url'=>'http://nytimes.com/'.$counter,
            'title'=>'Link '.$counter, 'post_key'=>$post_key, 'expanded_url'=>'http://nytimes.com/'.$counter, 'error'=>'', 'image_src'=>''));
            $counter--;
        }

        $insight_plugin = new EOYMostLinksInsight();
        $links = $insight_plugin->getYearOfLinks($this->instance);
        $this->assertEqual(count($links), 22);

        $domain_counts = $insight_plugin->getDomainCounts($links);
        // $this->debug(Utils::varDumpToString($domain_counts));
        $sorted_domains = array(
            0 => array('lifehacker.com' => 12),
            1 => array('nytimes.com' => 10)
        );

        $i = 0;
        foreach ($domain_counts as $domain => $count) {
            $this->assertEqual($sorted_domains[$i][$domain], $count);
            $i++;
        }

        $domain = $insight_plugin->getPopularDomain($domain_counts);
        $this->assertEqual('lifehacker.com', $domain);

        $posts = $insight_plugin->getMostPopularPostsLinkingTo($this->instance, $domain);
        // $this->debug(Utils::varDumpToString($posts));
        $this->assertEqual(3, count($posts));
        $this->assertEqual($posts[0]->id, 1761);
        $this->assertEqual($posts[2]->id, 1763);
    }

    public function testTwitterNormalCase() {
        $builders = self::setUpPublicInsight($this->instance);
        $year = Date('Y');
        $link_dao = DAOFactory::getDAO('LinkDAO');
        $user_id = $this->instance->author_id;
        $counter = 12;
        $days = 0;

        // set up most links
        while ($counter != 0) {
            $post_key = $counter + 1760;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>$this->instance->network, 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'retweet_count_cache' => $days,
            'reply_count_cache' => $days,
            'favlike_count_cache' => $days,
            'post_text'=>'Link post #' . $counter . ' http://lifehacker.com/'.$counter, 'pub_date'=>$today));

            $builders[] = FixtureBuilder::build('links', array('url'=>'http://lifehacker.com/'.$counter,
            'title'=>'Link '.$counter, 'post_key'=>$post_key, 'expanded_url'=>'http://lifehacker.com/'.$counter, 'error'=>'', 'image_src'=>''));
            $counter--;
        }

        // set up fewer links
        $counter = 10;
        $days = 0;
        while ($counter != 0) {
            $post_key = $counter + 1860;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>$this->instance->network, 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'post_text'=>'Link post http://nytimes.com/'.$counter, 'pub_date'=>$today));

            $builders[] = FixtureBuilder::build('links', array('url'=>'http://nytimes.com/'.$counter,
            'title'=>'Link '.$counter, 'post_key'=>$post_key, 'expanded_url'=>'http://nytimes.com/'.$counter, 'error'=>'', 'image_src'=>''));
            $counter--;
        }

        $posts = array();
        $insight_plugin = new EOYMostLinksInsight();
        $insight_plugin->generateInsight($this->instance, null, $posts, 3);

        // Assert that insight got inserted
        $insight_dao = new InsightMySQLDAO();
        $today = date ('Y-m-d');
        $result = $insight_dao->getInsight('eoy_most_links', $this->instance->id, $today);
        $this->assertNotNull($result);
        $this->assertIsA($result, "Insight");
        // $this->assertEqual(1, count($result->posts));
        $this->assertEqual("ICYMI: @ev's most linked-to site of $year", $result->headline);
        $this->assertEqual("What's Twitter without the tabs? In $year, @ev shared " .
            "more #content from lifehacker.com than from any other web site. These " .
            "were the most popular tweets linking to lifehacker.com.", $result->text);

        $this->dumpRenderedInsight($result, "Normal case, Twitter");
        // $this->dumpAllHTML();
    }

    public function testTwitterOneMatch() {
        $builders = self::setUpPublicInsight($this->instance);
        $year = Date('Y');
        $link_dao = DAOFactory::getDAO('LinkDAO');
        $user_id = $this->instance->author_id;
        $counter = 1;
        $days = 0;

        // set up most links
        while ($counter != 0) {
            $post_key = $counter + 1760;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>$this->instance->network, 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'retweet_count_cache' => $days,
            'reply_count_cache' => $days,
            'favlike_count_cache' => $days,
            'post_text'=>'Link post #' . $counter . ' http://lifehacker.com/'.$counter, 'pub_date'=>$today));

            $builders[] = FixtureBuilder::build('links', array('url'=>'http://lifehacker.com/'.$counter,
            'title'=>'Link '.$counter, 'post_key'=>$post_key, 'expanded_url'=>'http://lifehacker.com/'.$counter, 'error'=>'', 'image_src'=>''));
            $counter--;
        }

        $posts = array();
        $insight_plugin = new EOYMostLinksInsight();
        $insight_plugin->generateInsight($this->instance, null, $posts, 3);

        // Assert that insight got inserted
        $insight_dao = new InsightMySQLDAO();
        $today = date ('Y-m-d');
        $result = $insight_dao->getInsight('eoy_most_links', $this->instance->id, $today);
        $this->assertNotNull($result);
        $this->assertIsA($result, "Insight");
        // $this->assertEqual(1, count($result->posts));
        $this->assertEqual("ICYMI: @ev's most linked-to site of $year", $result->headline);
        $this->assertEqual("What's Twitter without the tabs? In $year, @ev shared " .
            "more #content from lifehacker.com than from any other web site. This " .
            "was the most popular tweet linking to lifehacker.com.", $result->text);

        $this->dumpRenderedInsight($result, "One post, Twitter");
        // $this->dumpAllHTML();
    }

    public function testTwitterNoMatch() {
        $builders = self::setUpPublicInsight($this->instance);
        $year = Date('Y');
        $link_dao = DAOFactory::getDAO('LinkDAO');
        $user_id = $this->instance->author_id;
        $counter = 1;
        $days = 0;

        // set up no links
        while ($counter != 0) {
            $post_key = $counter + 1760;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>$this->instance->network, 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'retweet_count_cache' => $days,
            'reply_count_cache' => $days,
            'favlike_count_cache' => $days,
            'post_text'=>'Link post #' . $counter . ' http://lifehacker.com/'.$counter, 'pub_date'=>$today));

            $counter--;
        }

        $posts = array();
        $insight_plugin = new EOYMostLinksInsight();
        $insight_plugin->generateInsight($this->instance, null, $posts, 3);

        // Assert that insight got inserted
        $insight_dao = new InsightMySQLDAO();
        $today = date ('Y-m-d');
        $result = $insight_dao->getInsight('eoy_most_links', $this->instance->id, $today);
        $this->assertNotNull($result);
        $this->assertIsA($result, "Insight");
        // $this->assertEqual(1, count($result->posts));
        $this->assertEqual("@ev tweeted nary a link in $year", $result->headline);
        $this->assertEqual("This year, @ev didn't post a single link on Twitter. " .
            "You can do better than that, internet!", $result->text);

        $this->dumpRenderedInsight($result, "No match, Twitter");
        // $this->dumpAllHTML();
    }

    public function testFacebookNormalCase() {
        $this->instance->network_username = 'Mark Zuckerberg';
        $this->instance->network = 'facebook';
        $builders = self::setUpPublicInsight($this->instance);
        $year = Date('Y');
        $link_dao = DAOFactory::getDAO('LinkDAO');
        $user_id = $this->instance->author_id;
        $counter = 12;
        $days = 0;

        // set up most links
        while ($counter != 0) {
            $post_key = $counter + 1760;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>$this->instance->network, 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'retweet_count_cache' => $days,
            'reply_count_cache' => $days,
            'favlike_count_cache' => $days,
            'post_text'=>'Link post #' . $counter . ' http://lifehacker.com/'.$counter, 'pub_date'=>$today));

            $builders[] = FixtureBuilder::build('links', array('url'=>'http://lifehacker.com/'.$counter,
            'title'=>'Link '.$counter, 'post_key'=>$post_key, 'expanded_url'=>'http://lifehacker.com/'.$counter, 'error'=>'', 'image_src'=>''));
            $counter--;
        }

        // set up fewer links
        $counter = 10;
        $days = 0;
        while ($counter != 0) {
            $post_key = $counter + 1860;
            $today = date("$year-m-d H:i",strtotime("-$days minutes"));
            $days++;

            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_key, 'post_id'=>$post_key,
            'network'=>$this->instance->network, 'author_user_id'=>$user_id, 'author_username'=>'user','in_reply_to_user_id' => NULL,
            'in_retweet_of_post_id' => NULL,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User',
            'post_text'=>'Link post http://nytimes.com/'.$counter, 'pub_date'=>$today));

            $builders[] = FixtureBuilder::build('links', array('url'=>'http://nytimes.com/'.$counter,
            'title'=>'Link '.$counter, 'post_key'=>$post_key, 'expanded_url'=>'http://nytimes.com/'.$counter, 'error'=>'', 'image_src'=>''));
            $counter--;
        }


        $posts = array();
        $insight_plugin = new EOYMostLinksInsight();
        $insight_plugin->generateInsight($this->instance, null, $posts, 3);

        // Assert that insight got inserted
        $insight_dao = new InsightMySQLDAO();
        $today = date ('Y-m-d');
        $result = $insight_dao->getInsight('eoy_most_links', $this->instance->id, $today);
        $this->assertNotNull($result);
        $this->assertIsA($result, "Insight");
        $year = date('Y');
        $this->assertEqual("Mark Zuckerberg's most shared site of $year", $result->headline);
        $this->assertEqual("Looks like lifehacker.com owes Mark Zuckerberg a thank you. " .
            "In $year, Mark Zuckerberg directed friends to lifehacker.com more than to " .
            "any other site. These links were the most popular.", $result->text);

        // $this->dumpRenderedInsight($result, "Normal case, Facebook");
        $this->dumpAllHTML();
    }

    private function dumpAllHTML() {
        $controller = new InsightStreamController();
        $_GET['u'] = $this->instance->network_username;
        $_GET['n'] = $this->instance->network;
        $_GET['d'] = date ('Y-m-d');
        $_GET['s'] = 'eoy_most_links';
        $results = $controller->go();
        //output this to an HTML file to see the insight fully rendered
        $this->debug($results);
    }

    private function dumpRenderedInsight($result, $message) {
        return false;
        if (isset($message)) {
            $this->debug("<h4 style=\"text-align: center; margin-top: 20px;\">$message</h4>");
        }
        $this->debug($this->getRenderedInsightInHTML($result));
        $this->debug($this->getRenderedInsightInEmail($result));
    }
}

