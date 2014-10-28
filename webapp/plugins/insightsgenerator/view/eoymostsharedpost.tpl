{* {include file=|cat:'_header.tpl'} *}
{*  *}
{* {include file=|cat:'_textonly.tpl' icon='user'} *}

{include file=$tpl_path|cat:"_posts_with_counts.tpl" posts=$i->related_data.posts hide_avatar='true'}

{* {include file=|cat:'_footer.tpl'} *}

