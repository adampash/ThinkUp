<?php
/*
 Plugin Name: Exclamation count (End of Year)
 Description: How often you used exclamation points this year!!!!!
 When: December 5
 */

/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/eoyexclamationcount.php
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

class EOYExclamationCountInsight extends InsightPluginParent implements InsightPlugin {
// class EOYExclamationCountInsight extends CriteriaMatchInsightPluginParent implements InsightPlugin {

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
            $insight->slug = 'eoy_exclamation_count';
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
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            );
            foreach ($months as $month) {
                $point_chart[$month] = 0;
            }
            foreach ($last_year_of_posts as $post) {
                if ($this->hasExclamationPoint($post->post_text)) {
                    $date = new DateTime($post->pub_date);
                    $month = $date->format('F');
                    $point_chart[$month]++;
                    $count++;
                }
                $total_posts++;
            }
            $percent = round($count / $total_posts * 100);

            $copy = array(
                'twitter' => array(
                    'normal' => array(
                        'headline' => "The !!!'s of Twitter, $year",
                        'body' => "OMG! In $year, %username used exclamation points " .
                            "in <strong>%total tweets</strong>. That's %percent% " .
                            "of @username's total tweets this year!"
                    ),
                    'none' => array(
                        'headline' => "%username is unimpressed with $year",
                        'body' => "In $year, %username didn't use one exclamation " .
                            "point on %network. Must be holding out for something " .
                            "really exciting!"
                    )
                ),
                'facebook' => array(
                    'normal' => array(
                        'headline' => "%username's emphatic $year on Facebook!",
                        'body' => "Enthusiasm is contagious, and in $year, %username " .
                            "spread the excitement in a total of <strong>%total posts" .
                            "</strong> containing exclamation points. " .
                            "That's %percent% of %username's total Facebook posts " .
                            "this year!"
                    ),
                    'none' => array(
                        'headline' => "%username is unimpressed with $year",
                        'body' => "In $year, %username didn't use one exclamation " .
                            "point on %network. Must be holding out for something " .
                            "really exciting!"
                    )
                )
            );

            if ($count > 0) {
                $type = 'normal';
                $rows = array();
                foreach ($point_chart as $label => $number) {
                    $rows[] = array('c'=>array(array('v'=>$label), array('v' => $number)));
                }
                // $insight->related_data = serialize($point_chart);
                // $insight->related_data = array(
                //     'vis_data' => serialize($point_chart)
                // );
                // $insight->related_data = (array(
                //     'cols' => array(
                //         array('label' => 'Month', 'type' => 'string'),
                //         array('label' => 'Occurences', 'type' => 'number'),
                //     ),
                //     'rows' => $rows
                // ));
                $insight->setBarChart(array(
                    'cols' => array(
                        array('label' => 'Month', 'type' => 'string'),
                        array('label' => 'Occurences', 'type' => 'number'),
                    ),
                    'rows' => $rows
                ));
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
                    'total' => $count,
                    'percent' => $percent,
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

    public function hasExclamationPoint($post_text) {
        $does_match = preg_match_all('/!+/', $post_text);
        return $does_match;
    }

}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('EOYExclamationCountInsight');
