<?php
/*
 Plugin Name: Most shared post (EOY)
 Description: User's most shared/retweeted post in current year
 */
/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/eoymostsharedpost.php
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
 * EOYMostSharedPost (name of file)
 *
 * Description of what this class does
 *
 * Copyright (c) 2013 Adam Pash
 *
 * @author Adam Pash adam.pash@gmail.com
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2013 Adam Pash
 */

class EOYMostSharedPostInsight extends InsightPluginParent implements InsightPlugin {

    public function generateInsight(Instance $instance, User $user, $last_week_of_posts, $number_days) {
        if (Utils::isTest() || date("Y-m-d") == '2014-12-01') {
            parent::generateInsight($instance, $last_week_of_posts, $number_days);
            $this->logger->logInfo("Begin generating insight", __METHOD__.','.__LINE__);
            $filename = basename(__FILE__, ".php");

            $top_three_shared = $this->topThreeThisYear($instance);

            $insight = new Insight();
            $insight->instance_id = $instance->id;
            $insight->slug = 'eoy_most_shared';
            $insight->date = date('Y-m-d');
            $insight->eoy = true;

            $year = date('Y');

            $copy = array(
                'twitter' => array(
                    'normal' => array(
                        'headline' => "%username's most-retweeted tweet of $year",
                        'body' => "Tweet, retweet, repeat. In $year, %username earned the most retweets for these gems."
                    ),
                    'one' => array(
                        'headline' => "%username's most-retweeted tweet of $year",
                        'body' => "Tweet, retweet, repeat. In $year, %username earned the most retweets for this gem."
                    ),
                    'none' => array(
                        'headline' => "Retweets aren't everything",
                        'body' => "%username didn't get any retweets in $year, which is a-okay. We're not all here to broadcast."
                    ),
                ),
                'facebook' => array(
                    'normal' => array(
                        'headline' => "%username's most-shared status update of $year",
                        'body' => "With shares on the rise, $year was a bull market for %username's most-shared status updates."
                    ),
                    'one' => array(
                        'headline' => "%username's most-shared status update of $year",
                        'body' => "With shares on the rise, $year was a bull market for %username's most-shared status update."
                    ),
                    'none' => array(
                        'headline' => "Shares aren't everything",
                        'body' => "No one shared %username's status updates on Facebook in $year — not that there's anything wrong with that. Sometimes it's best to keep things close-knit."
                    ),
                )
            );

            $network = $instance->network;
            $year = date('Y');

            if (sizeof($top_three_shared) > 1) {
                $type = 'normal';
            }
            else if (sizeof($top_three_shared) == 1) {
                $type = 'one';
            }
            else {
                $type = 'none';
            }

            $insight->headline = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['headline']
                )
            );
            $insight->text = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['body']
                )
            );

            $insight->emphasis = Insight::EMPHASIS_HIGH;
            $insight->filename = $filename;

            foreach ($top_three_shared as $post) {
                $share_type = $network == 'twitter' ? " retweets" : " shares";
                if ($post->retweet_count_cache == 1) {
                    $share_type = substr($share_type, 0, -1);
                }
                $post->count = $post->retweet_count_cache . $share_type;
            }
            $insight->setPosts($top_three_shared);

            $this->insight_dao->insertInsight($insight);
            $insight = null;

            $this->logger->logInfo("Done generating insight", __METHOD__.','.__LINE__);
        }
    }

    public function topThreeThisYear(Instance $instance, $order='retweets') {
        $post_dao = DAOFactory::getDAO('PostDAO');
        $days = $this->daysSinceJanFirst();

        $posts = $post_dao->getAllPostsByUsernameOrderedBy(
            $instance->network_username,
            $network=$instance->network,
            $count=3,
            $order_by=$order,
            $in_last_x_days = $days,
            $iterator = false,
            $is_public = false
        );
        return $posts;
    }

    public static function daysSinceJanFirst() {
        $year = date('Y');
        return (int) floor((time() - strtotime("01-01-$year"))/(60*60*24));
    }
}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('EOYMostSharedPostInsight');

