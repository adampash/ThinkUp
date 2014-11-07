<?php
/*
 Plugin Name: All About You (End of Year)
 Description: How often you referred to yourself ("I", "me", "myself", "my") in the past year.
 When: December 2
 */

/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/eoyallaboutyou.php
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
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2012-2014 Gina Trapani
 */

class EOYAllAboutYouInsight extends InsightPluginParent implements InsightPlugin {

    public function generateInsight(Instance $instance, User $user, $last_week_of_posts, $number_days) {
        parent::generateInsight($instance, $user, $last_week_of_posts, $number_days);
        $this->logger->logInfo("Begin generating insight", __METHOD__.','.__LINE__);
        $insight_text = '';

        // $should_generate_insight = self::shouldGenerateAnnualInsight( 'eoy_all_about_you', $instance, $insight_date='today',
        //     $regenerate_existing_insight=false, $day_of_week = $day_of_week, count($last_week_of_posts));
        $should_generate_insight = true;
        if ($should_generate_insight) {
            $text = '';
            $count = 0;
            $last_year_of_posts = getYearOfPostsIterator($this->username, $instance->network);
            $total_posts = 0;
            foreach ($last_year_of_posts as $post) {
                $count += self::hasFirstPersonReferences($post->post_text) ? 1 : 0;
                $total_posts++;
            }

            $headline = $this->getVariableCopy(array(
                "A year's worth of %username"
            ));
            $percent = round($count / $total_posts * 100);
            $plural = count($last_week_of_posts) > 1;
            $insight_text = "<strong>$percent%</strong> of %username's tweets " .
                " contained the words &ldquo;I&rdquo;, &ldquo;me&rdquo;, " .
                "&ldquo;my&rdquo;, &ldquo;mine&rdquo;, or &ldquo;myself&rdquo; " .
                "in $year. Sometimes, you've just got to get personal.";

            $my_insight = new Insight();

            $my_insight->slug = 'eoy_all_about_you';
            $my_insight->instance_id = $instance->id;
            $my_insight->date = $this->insight_date;
            $my_insight->headline = $headline;
            $my_insight->text = $insight_text;
            $my_insight->header_image = $header_image;
            $my_insight->filename = basename(__FILE__, ".php");
            $my_insight->emphasis = Insight::EMPHASIS_HIGH;

            $this->insight_dao->insertInsight($my_insight);
        }

        $this->logger->logInfo("Done generating insight", __METHOD__.','.__LINE__);
    }

    /**
     * Determine if "I", "me", "my", "myself" or "mine" appear in text.
     * @param str $text
     * @return bool Does "I", "me", "my", "myself" or "mine" appear in $text
     */
    public static function hasFirstPersonReferences($text) {
        $count = 0;
        $matches = array();
        $url_free_text = preg_replace('!https?://[\S]+!', ' ', $text);
        $depunctuated_text = " ". preg_replace('/[^a-z0-9]+/i', ' ', $url_free_text) ." ";

        preg_match_all('/\b(?:I|myself|my|mine)\b/i', $depunctuated_text, $matches);
        $notmes = count($matches[0]);
        preg_match_all('/\b\.me\b/i', $text, $me_matches);
        $dotmes = count($me_matches[0]);
        preg_match_all('/\bme\b/i', $text, $me_matches);
        $mes = count($me_matches[0]);

        if ($notmes || $mes > $dotmes) {
            return true;
        }
        return false;
    }
}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('EOYAllAboutYouInsight');
