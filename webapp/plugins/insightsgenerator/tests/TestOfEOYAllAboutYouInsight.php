<?php
/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/tests/TestOfEOYAllAboutYouInsight.php
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
 * Test of EOYAllAboutYouInsight
 *
 * Test for the EOYAllAboutYouInsight class.
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
require_once THINKUP_ROOT_PATH. 'webapp/plugins/insightsgenerator/insights/eoyallaboutyou.php';

class TestOfEOYAllAboutYouInsight extends ThinkUpInsightUnitTestCase {

    public function setUp(){
        parent::setUp();
        $instance = new Instance();
        $instance->id = 10;
        $instance->network_username = 'ev';
        $instance->author_id = '18';
        $instance->network = 'twitter';
        $this->instance = $instance;
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testYearOfPostsIterator() {
        $insight_plugin = new EOYAllAboutYouInsight();

        for ($i=0; $i<5; $i++) {
            $builders[] = FixtureBuilder::build('posts',
                array(
                    'post_text' => 'This is a post',
                    'pub_date' => '2014-02-07',
                    'author_username' => $this->instance->network_username,
                    'network' => $this->instance->network,
                )
            );
        }

        $posts = $insight_plugin->getYearOfPosts($this->instance);
        $this->assertIsA($posts,'PostIterator');
        foreach($posts as $key => $value) {
            $this->assertEqual($value->post_text, "This is a post");
        }
    }

    public function testTwitterNormalCase() {
        // set up all-about-me posts
        $builders = self::setUpPublicInsight($this->instance);
        for ($i=0; $i<5; $i++) {
            $builders[] = FixtureBuilder::build('posts',
                array(
                    'post_text' => 'This is a post that I did!',
                    'pub_date' => '2014-02-07',
                    'author_username' => $this->instance->network_username,
                    'network' => $this->instance->network,
                )
            );
        }
        // set up normal non-me posts
        for ($i=0; $i<5; $i++) {
            $builders[] = FixtureBuilder::build('posts',
                array(
                    'post_text' => 'This is a post',
                    'pub_date' => '2014-02-07',
                    'author_username' => $this->instance->network_username,
                    'network' => $this->instance->network,
                )
            );
        }
        $posts = array();
        $insight_plugin = new EOYAllAboutYouInsight();
        $insight_plugin->generateInsight($this->instance, null, $posts, 3);
        //
        // Assert that insight got inserted
        $insight_dao = new InsightMySQLDAO();
        $today = date ('Y-m-d');
        $result = $insight_dao->getInsight('eoy_all_about_you', $this->instance->id, $today);
        $this->assertNotNull($result);
        $this->assertIsA($result, "Insight");
        $year = date('Y');
        $this->assertEqual("A year's worth of @ev", $result->headline);
        $this->assertEqual("In $year, <strong>50%</strong> of @ev's tweets " .
            "&mdash; a grand total of 5 of them &mdash; contained the words " .
            "&ldquo;I&rdquo;, &ldquo;me&rdquo;, &ldquo;my&rdquo;, " .
            "&ldquo;mine&rdquo;, or &ldquo;myself&rdquo;. Sometimes, you've " .
            "just got to get personal.", $result->text);

        $this->dumpRenderedInsight($result, "Normal case, Twitter");
        // $this->dumpAllHTML();
    }

    public function testTwitterNoMatches() {
        // set up all-about-me posts
        $builders = self::setUpPublicInsight($this->instance);
        // set up normal non-me posts
        for ($i=0; $i<5; $i++) {
            $builders[] = FixtureBuilder::build('posts',
                array(
                    'post_text' => 'This is a post',
                    'pub_date' => '2014-02-07',
                    'author_username' => $this->instance->network_username,
                    'network' => $this->instance->network,
                )
            );
        }
        $posts = array();
        $insight_plugin = new EOYAllAboutYouInsight();
        $insight_plugin->generateInsight($this->instance, null, $posts, 3);
        //
        // Assert that insight got inserted
        $insight_dao = new InsightMySQLDAO();
        $today = date ('Y-m-d');
        $result = $insight_dao->getInsight('eoy_all_about_you', $this->instance->id, $today);
        $this->assertNotNull($result);
        $this->assertIsA($result, "Insight");
        $year = date('Y');
        $this->assertEqual("A year's worth of @ev", $result->headline);
        $this->assertEqual("In $year, none of @ev's tweets contained the words " .
            "&ldquo;I&rdquo;, &ldquo;me&rdquo;, &ldquo;my&rdquo;, " .
            "&ldquo;mine&rdquo;, or &ldquo;myself&rdquo;. Sometimes, you've " .
            "just got to get personal &mdash; unless you're @ev, apparently!", $result->text);

        $this->dumpRenderedInsight($result, "No matches, Twitter");
        // $this->dumpAllHTML();
    }

    private function dumpAllHTML() {
        $controller = new InsightStreamController();
        $_GET['u'] = $this->instance->network_username;
        $_GET['n'] = $this->instance->network;
        $_GET['d'] = date ('Y-m-d');
        $_GET['s'] = 'eoy_all_about_you';
        $results = $controller->go();
        //output this to an HTML file to see the insight fully rendered
        $this->debug($results);
    }

    private function dumpRenderedInsight($result, $message) {
        // return false;
        if (isset($message)) {
            $this->debug("<h4 style=\"text-align: center; margin-top: 20px;\">$message</h4>");
        }
        $this->debug($this->getRenderedInsightInHTML($result));
        $this->debug($this->getRenderedInsightInEmail($result));
    }
}

