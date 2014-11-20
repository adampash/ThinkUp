<?php
/*
 Plugin Name: Most linked-to site (End of Year)
 Description: The site you linked to the most this year
 When: December 12
 */

/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/eoymostlinks.php
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
 * Copyright (c) 2014 Adam Pash
 *
 * @author Adam Pash adam.pash@gmail.com
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2014 Adam Pash
 */

class EOYMostLinksInsight extends InsightPluginParent implements InsightPlugin {

    var $posts_by_domain = array();

    public function generateInsight(Instance $instance, User $user, $last_week_of_posts, $number_days) {
        parent::generateInsight($instance, $user, $last_week_of_posts, $number_days);
        $this->logger->logInfo("Begin generating insight", __METHOD__.','.__LINE__);
        $insight_text = '';

        // $should_generate_insight = self::shouldGenerateAnnualInsight( 'eoy_exclamation_count', $instance, $insight_date='today',
        //     $regenerate_existing_insight=false, $day_of_week = $day_of_week, count($last_week_of_posts));
        $should_generate_insight = true;
        if ($should_generate_insight) {
            $insight = new Insight();
            $insight->instance_id = $instance->id;
            $insight->slug = 'eoy_most_links';
            $insight->date = date('Y-m-d');
            $insight->eoy = true;

            $count = 0;
            $post_dao = DAOFactory::getDAO('PostDAO');
            $year = date('Y');
            $network = $instance->network;

            $last_year_of_posts = $this->getYearOfLinks($instance);
            $domain_counts = $this->getDomainCounts($last_year_of_posts);
            $popular_domain = $this->getPopularDomain($domain_counts);
            $posts = $this->getMostPopularPostsLinkingTo($instance, $popular_domain);

            $copy = array(
                'twitter' => array(
                    'normal' => array(
                        'headline' => "ICYMI: %username's most linked-to site of $year",
                        'body' => "What's Twitter without the tabs? In $year, %username " .
                            "shared more #content from %domain than from any other web " .
                            "site. These were the most popular tweets linking to %domain."
                    ),
                    'one' => array(
                        'headline' => "ICYMI: %username's most linked-to site of $year",
                        'body' => "What's Twitter without the tabs? In $year, %username " .
                            "shared more #content from %domain than from any other web " .
                            "site. This was the most popular tweet linking to %domain."
                    ),
                    'none' => array(
                        'headline' => "%username tweeted nary a link in $year",
                        'body' => "This year, %username didn't post a single link on " .
                            "Twitter. You can do better than that, internet!"
                    )
                ),
                'facebook' => array(
                    'normal' => array(
                        'headline' => "%username's most shared site of $year",
                        'body' => "Looks like %domain owes %username a thank you. In " .
                            "$year, %username directed friends to %domain more than " .
                            "to any other site. These links were the most popular."
                    ),
                    'one' => array(
                        'headline' => "%username's most popular picture on Facebook, $year",
                        'body' => "What's a newsfeed without the photos? In $year, " .
                            "this was the most popular pic %username shared on Facebook."
                    ),
                    'none' => array(
                        'headline' => "No photos on Facebook?",
                        'body' => "%username didn't share any pics on %network this year. " .
                            "Bummer! We had a really great insight for photos. On the " .
                            "plus side: Guess %username won't need to worry about " .
                            "leaked nudes!"
                    )
                )
            );

            if (sizeof($posts) > 1) {
                $type = 'normal';
            } else if (sizeof($posts) == 1) {
                $type = 'one';
            } else {
                $type = 'none';
            }
            $headline = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['headline']
                )
            );

            $insight_text = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['body']
                ),
                array(
                    'domain' => $popular_domain
                )
            );

            $insight->headline = $headline;
            $insight->text = $insight_text;
            $insight->setPosts($posts);
            $insight->filename = basename(__FILE__, ".php");
            $insight->emphasis = Insight::EMPHASIS_HIGH;

            $this->insight_dao->insertInsight($insight);
        }

        $this->logger->logInfo("Done generating insight", __METHOD__.','.__LINE__);
    }

    /**
     * Get year of posts as an iterator
     * @param Instance $instance
     * @return PostIterator $posts
     */
    public function getYearOfLinks(Instance $instance) {
        $link_dao = DAOFactory::getDAO('LinkDAO');
        $days = Utils::daysSinceJanFirst();

        $links = $link_dao->getLinksByUserSinceDaysAgo($instance->network_user_id, $instance->network, 0, $days);

        return $links;
    }

    /**
     * Get at most three most popular posts that linked to this domain
     * @param Instance $instance
     * @param String $domain
     * @return array $posts
     */
    public function getMostPopularPostsLinkingTo(Instance $instance, $domain) {
        $posts = array();
        $post_ids = $this->posts_by_domain[$domain];
        $post_dao = DAOFactory::getDAO('PostDAO');
        foreach ($post_ids as $post_id) {
            $post = $post_dao->getPost($post_id, $instance->network);
            $popularity_index = Utils::getPopularityIndex($post);
            if (isset($posts[$popularity_index])) {
                $popularity_index++;
            }
            $posts[$popularity_index] = $post;
        }
        krsort($posts);
        return array_slice($posts, 0, 3);
    }

    /**
     * Get counts for domains in last year of links
     * @param array $links
     * @return array $domain_counts
     */
    public function getDomainCounts($links) {
        $total_counts = array();
        // $posts_by_domain = array();
        $tweet_counts = array();
        $retweet_counts = array();
        foreach ($links as $link) {
            if($link['expanded_url'] == "" || !empty($link['image_src'])) {
                continue;
            } else {
                $url = parse_url($link['expanded_url']);
                $domain = $url['host'];
            }

            if(!array_key_exists($domain, $total_counts)) {
                $total_counts[$domain] = 0;
            }
            if(!array_key_exists($domain, $this->posts_by_domain)) {
                $this->posts_by_domain[$domain] = array();
            }
            if(!array_key_exists($domain, $tweet_counts)) {
                $tweet_counts[$domain] = 0;
            }
            if(!array_key_exists($domain, $retweet_counts)) {
                $retweet_counts[$domain] = 0;
            }
            $total_counts[$domain]++;
            if ($link['in_retweet_of_post_id']) {
                $retweet_counts[$domain]++;
            }
            else {
                $tweet_counts[$domain]++;
                $this->posts_by_domain[$domain][] = $link['post_key'];
            }
        }
        arsort($total_counts);
        return $total_counts;
    }

    /**
     * Get most popular domain from array of linked-to domains
     * @param array $domain_counts
     * @return String $popular_url
     */
    public function getPopularDomain($domain_counts) {
        $popular_url = array_search(max($domain_counts), $domain_counts);
        return $popular_url;
    }
}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('EOYMostLinksInsight');

