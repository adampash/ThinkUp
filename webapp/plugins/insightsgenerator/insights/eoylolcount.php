<?php
/*
 Plugin Name: LOL count (End of Year)
 Description: How often you LOLed in a post this year
 When: December 13
 */

/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/eoylolcount.php
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

class EOYLOLCountInsight extends InsightPluginParent implements InsightPlugin {
// class EOYLOLCountInsight extends CriteriaMatchInsightPluginParent implements InsightPlugin {

    /**
     * @var array Posts that will be included in the insight
     */
    var $posts_to_include = array();

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
            $insight->slug = 'eoy_lol_count';
            $insight->date = date('Y-m-d');
            $insight->eoy = true;

            $count = 0;
            $post_dao = DAOFactory::getDAO('PostDAO');
            $year = date('Y');
            $network = $instance->network;

            /**
             * Track occurences of exclamations per month
             */
            $point_chart = array();

            $last_year_of_posts = $this->getYearOfPosts($instance);
            $total_posts = 0;

            $months = array(
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            );
            foreach ($months as $month) {
                $point_chart[$month] = 0;
            }
            foreach ($last_year_of_posts as $post) {
                if ($this->hasLOL($post)) {
                    $date = new DateTime($post->pub_date);
                    $month = $date->format('M');
                    $point_chart[$month]++;
                    $count++;
                }
                $total_posts++;
            }
            $percent = round($count / $total_posts * 100);

            $max_month = $this->getMaxMonth($point_chart);

            $copy = array(
                'twitter' => array(
                    'normal' => array(
                        'headline' => "omg lol @twitter, $year",
                        'body' => "%username found %total things to LOL about on " .
                            "Twitter in $year, including these LOLed-at tweets."
                    ),
                    'one' => array(
                        'headline' => "Funny, but rarely LOL funny",
                        'body' => "%username found 1 thing to LOL about on " .
                            "Twitter in $year."
                    )
                ),
                'facebook' => array(
                    'normal' => array(
                        'headline' => "The LOLs of Facebook, $year",
                        'body' => "ROFL. %username LOLed at %total things on Facebook " .
                            "in $year, including these LOL-worthy status updates."
                    ),
                    'one' => array(
                        'headline' => "The LOLs of Facebook, $year",
                        'body' => "ROFL. %username LOLed once on Facebook " .
                            "in $year."
                    )
                )
            );

            if ($count === 0) {
                return;
            }
            if ($count > 1) {
                $type = 'normal';
                $rows = array();
                foreach ($point_chart as $label => $number) {
                    $rows[] = array('c'=>array(array('v'=>$label), array('v' => $number)));
                }
                $insight->setBarChart(array(
                    'cols' => array(
                        array('label' => 'Month', 'type' => 'string'),
                        array('label' => 'Occurences', 'type' => 'number'),
                    ),
                    'rows' => $rows
                ));
            } else {
                $type = 'one';
            }
            $headline = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['headline']
                ),
                array(
                    'total' => $count
                )
            );

            $insight_text = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['body']
                ),
                array(
                    'total' => $count,
                    'percent' => $percent,
                    'month' => $max_month,
                    'network' => ucfirst($network)
                )
            );

            $insight->headline = $headline;
            $insight->text = $insight_text;
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
    public function getYearOfPosts(Instance $instance) {
        $post_dao = DAOFactory::getDAO('PostDAO');
        $days = Utils::daysSinceJanFirst();

        $posts = $post_dao->getAllPostsByUsernameOrderedBy(
            $instance->network_username,
            $network=$instance->network,
            $count=0,
            $order_by='pub_date',
            $in_last_x_days = $days,
            $iterator = true,
            $is_public = false
        );
        return $posts;
    }

    public function getMaxMonth($point_chart) {
        $short_month = array_search(max($point_chart),$point_chart);
        return date('F', strtotime("$short_month 1 2014"));
    }

    public function hasLOL(Post $post) {
        $text = strtolower($post->post_text);
        $has_lol = preg_match('/(\W|^)(lol|lolol.*|lol.*ing|loled|rofl.*|lmao.*|haha[ha]*)(\W|$)/', $text);

        if ($has_lol && $post->in_reply_to_post_id) {
            $post_dao = DAOFactory::getDAO('PostDAO');
            $post = $post_dao->getPost($post->in_reply_to_post_id, $instance->network);
            if ($post) {
                // TODO create associative array of post_id => popularity_score
                // $this->posts_to_include[] = $post;
            }
        }
        return $has_lol;
    }

}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('EOYLOLCountInsight');
