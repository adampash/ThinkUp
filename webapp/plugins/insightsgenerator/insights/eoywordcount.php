<?php
/*
 Plugin Name: Word count (End of Year)
 Description: How many words you posted this year.
 When: December 10
 */

/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/eoywordcount.php
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

class EOYWordCountInsight extends InsightPluginParent implements InsightPlugin {
// class EOYWordCountInsight extends CriteriaMatchInsightPluginParent implements InsightPlugin {

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
            $insight->slug = 'eoy_word_count';
            $insight->date = date('Y-m-d');
            $insight->eoy = true;

            $count = 0;
            $post_dao = DAOFactory::getDAO('PostDAO');
            $year = date('Y');
            $network = $instance->network;

            // Track occurences of words per month
            $point_chart = array();

            $last_year_of_posts = $this->getYearOfPosts($instance);

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
            $word_count = 0;
            foreach ($last_year_of_posts as $post) {
                $post_word_count = $this->countWords($post->post_text);
                $word_count += $post_word_count;
                $date = new DateTime($post->pub_date);
                $month = $date->format('M');
                $point_chart[$month] += $post_word_count;
                $count++;
            }

            $max_month = $this->getMaxMonth($point_chart);
            $month_words = number_format($point_chart[substr($max_month, 0, 3)]);
            $word_count = number_format($word_count);

            $copy = array(
                'twitter' => array(
                    'normal' => array(
                        'headline' => "%total words at 140 characters or less",
                        'body' => "In $year, %username entered a total of %total words " .
                            "into the Twitter data entry box, reaching peak wordage " .
                            "in %month, with %words_in_month words. Here's the " .
                            "month-by-month breakdown."
                    )
                ),
                'facebook' => array(
                    'normal' => array(
                        'headline' => "%username has a word or two for Facebook",
                        'body' => "In $year, %username safely delivered %total words " .
                            "to Facebook via the status update or comment box, topping " .
                            "out with %words_in_month words in %month. Here's a " .
                            "breakdown by month."
                    )
                )
            );

            $type = 'normal';
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
            $headline = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['headline']
                ),
                array(
                    'total' => $word_count
                )
            );

            $insight_text = $this->getVariableCopy(
                array(
                    $copy[$network][$type]['body']
                ),
                array(
                    'total' => $word_count,
                    'percent' => $percent,
                    'month' => $max_month,
                    'words_in_month' => $month_words,
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

    public function countWords($str) {
        while (substr_count($str, "  ")>0){
            $str = str_replace("  ", " ", $str);
        }
        return substr_count($str, " ")+1;
    }

}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('EOYWordCountInsight');
