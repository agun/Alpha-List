{exp:alpha_list:alpha_links parse="inward" wrapper="div"}
	{exp:weblog:entries weblog="resources" disable="categories|member_data|pagination|trackbacks" rdf="off" dynamic="off" orderby="title" sort="asc" }
	{no_results}There are currently no results for these search/filter options.{/no_results}
	{section}<div class="clear"></div><div class="grid_8"><h2>{letter}</h2></div>{/section}

	<p><a href="{path="template_group/template"}">{title}</a></p>

	{/exp:weblog:entries}
{/exp:alpha_list:alpha_links}