{extends file="page.tpl"}
{block name="content"}
        <div class="infoline">
{if $nb_activities gt 0}
            {$nb_activities} {if $nb_activities != 1}{_T string="activities" domain="events"}{else}{_T string="acivity" domain="events"}{/if}
{/if}
            <div class="fright">
                <label for="nbshow">{_T string="Records per page:"}</label>
                <select name="nbshow" id="nbshow">
                    {html_options options=$nbshow_options selected=$numrows}
                </select>
                <noscript> <span><input type="submit" value="{_T string="Change"}" /></span></noscript>
            </div>
        </div>
        <table class="listing">
            <thead>
                <tr>
                    <th class="id_row">#</th>
                    <th class="left">
                        <a href="{path_for name="events_activities" data=["option" => {_T string='order' domain="routes"}, "value" => "GaletteEvents\Repository\Activities::ORDERBY_NAME"|constant]}">
                            {_T string="Name" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Activities::ORDERBY_NAME')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\ActivitiesList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left date_row">
                        <a href="{path_for name="events_activities" data=["option" => {_T string='order' domain="routes"}, "value" => "GaletteEvents\Repository\Activities::ORDERBY_DATE"|constant]}">
                            {_T string="Creation date" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Activities::ORDERBY_DATE')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\ActivitiesList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th>{_T string="Is active" domain="events"}</th>
                    <th class="actions_row">{_T string="Actions"}</th>
                </tr>
            </thead>
            <tbody>
{if $activities|@count}
    {foreach from=$activities item=activity key=ordre}
                <tr>
                    <td class="{$rclass} right" data-scope="id">{$ordre+1+($filters->current_page - 1)*$numrows}</td>
                    <td class="{$rclass} nowrap username_row" data-scope="row">
                        {assign var="aid" value=$activity->getId()}
                        <a href="{path_for name="events_activity" data=["action" => {_T string="edit" domain="routes"}, "id" => $aid]}">{$activity->getName()}</a>
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Creation date" domain="events"}">{$activity->getCreationDate()}</td>
                    <td class="{$rclass} id_row" data-title="{_T string="Is active" domain="events"}">
                        {if $activity->isActive()}
                            <img src="{base_url}/{$template_subdir}images/icon-on.png" alt="{_T string="Active" domain="events"}"/>
                        {else}
                            <img src="{base_url}/{$template_subdir}images/icon-off.png" alt="{_T string="Inactive"}"/>
                        {/if}
                    </td>
                    <td class="{$rclass} center nowrap actions_row">
                        <a href="{path_for name="events_activity" data=["action" => {_T string="edit" domain="routes"}, "id" => $aid]}"><img src="{base_url}/{$template_subdir}images/icon-edit.png" alt="{_T string="[mod]"}" width="16" height="16" title="{_T string="%activity: edit informations" pattern="/%activity/" replace=$activity->getName() domain="events"}"/></a>
                        <a class="delete" href="{path_for name="events_remove_activity" data=["id" => $activity->getId()]}"><img src="{base_url}/{$template_subdir}images/icon-trash.png" alt="{_T string="[del]"}" width="16" height="16" title="{_T string="%activity: remove from database" pattern="/%activity/" replace=$activity->getName() domain="events"}"/></a>
                    </td>
                </tr>
    {/foreach}
{else}
                <tr><td colspan="4" class="emptylist">{_T string="No activity has been found" domain="events"}</td></tr>
{/if}
            </tbody>
        </table>
{if $nb_activities gt 0}
        <div class="center cright">
            {_T string="Pages:"}<br/>
            <ul class="pages">{$pagination}</ul>
        </div>
{/if}
{/block}

{block name="javascripts"}
<script type="text/javascript">
    $(function() {
        {include file="js_removal.tpl"}
    });
</script>
{/block}
