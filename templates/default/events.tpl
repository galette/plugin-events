{extends file="page.tpl"}
{block name="content"}
        <form action="{path_for name="filter-eventslist"}" method="post" id="filtre">
        <div id="listfilter">
            <label for="filter_str">{_T string="Search:"}&nbsp;</label>
            <input type="text" name="filter_str" id="filter_str" value="{$filters->filter_str}" type="search" placeholder="{_T string="Enter a value"}"/>&nbsp;
            <input type="submit" class="inline" value="{_T string="Filter"}"/>
            <input type="submit" name="clear_filter" class="inline" value="{_T string="Clear filter"}"/>
        </div>
        <div class="infoline">
            {$nb_events} {if $nb_events != 1}{_T string="events" domain="events"}{else}{_T string="event" domain="events"}{/if}
            <div class="fright">
                <label for="nbshow">{_T string="Records per page:"}</label>
                <select name="nbshow" id="nbshow">
                    {html_options options=$nbshow_options selected=$numrows}
                </select>
                <noscript> <span><input type="submit" value="{_T string="Change"}" /></span></noscript>
            </div>
        </div>
        </form>
        <table class="listing">
            <thead>
                <tr>
                    <th class="id_row">#</th>
                    <th class="left">
                        <a href="{path_for name="events_events" data=["option" => {_T string='order' domain="routes"}, "value" => "GaletteEvents\Repository\Events::ORDERBY_NAME"|constant]}">
                            {_T string="Name" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Events::ORDERBY_NAME')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\EventsList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left">
                        <a href="{path_for name="events_events" data=["option" => {_T string='order' domain="routes"}, "value" => "GaletteEvents\Repository\Events::ORDERBY_DATE"|constant]}">
                            {_T string="Date" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Events::ORDERBY_DATE')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\EventsList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left">
                        <a href="{path_for name="events_events" data=["option" => {_T string='order' domain="routes"}, "value" => "GaletteEvents\Repository\Events::ORDERBY_TOWN"|constant]}">
                            {_T string="Town" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Events::ORDERBY_TOWN')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\EventsList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th>{_T string="Group" domain="events"}</th>
                    <th>{_T string="Open" domain="events"}</th>
{if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <th class="actions_row">{_T string="Actions"}</th>
{/if}
                </tr>
            </thead>
            <tbody>
{if $events|@count}
    {foreach from=$events item=event key=ordre}
                <tr>
                    <td class="{$rclass} right" data-scope="id">{$ordre+1+($filters->current_page - 1)*$numrows}</td>
                    <td class="{$rclass} nowrap username_row" data-scope="row">
                        {assign var="eid" value=$event->getId()}
                        {*<input type="checkbox" name="event_sel[]" value="{$id}"/>*}
                        <a href="{path_for name="events_event" data=["action" => {_T string="edit" domain="routes"}, "id" => $eid]}">{$event->getName()}</a>
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Date" domain="events"}">{$event->getBeginDate()}</td>
                    <td class="{$rclass}" data-title="{_T string="Town" domain="events"}">{$event->getTown()}</td>
                    <td class="{$rclass}" data-title="{_T string="Group" domain="events"}">{$event->getGroupName()}</td>
                    <td class="{$rclass}" data-title="{_T string="Open" domain="events"}">
                        {if $event->isOpen()}
                            <img src="{base_url}/{$template_subdir}images/icon-on.png" alt="{_T string="Open" domain="events"}" title="{_T string="Event is open" domain="events"}"/>
                        {else}
                            <img src="{base_url}/{$template_subdir}images/icon-off.png" alt="{_T string="Closed"}" title="{_T string="Event is closed" domain="events"}"/>
                        {/if}
                    </td>
    {if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <td class="{$rclass} center nowrap actions_row">
                        <a href="{path_for name="events_event" data=["action" => {_T string="edit" domain="routes"}, "id" => $eid]}"><img src="{base_url}/{$template_subdir}images/icon-edit.png" alt="{_T string="[mod]"}" width="16" height="16" title="{_T string="%eventname: edit informations" pattern="/%eventname/" replace=$event->getName() domain="events"}"/></a>
        {if $login->isAdmin() or $login->isStaff()}
                        <a class="delete" href="{path_for name="events_remove_event" data=["id" => $event->getId()]}"><img src="{base_url}/{$template_subdir}images/icon-trash.png" alt="{_T string="[del]"}" width="16" height="16" title="{_T string="%eventname: remove from database" pattern="/%eventname/" replace=$event->getName() domain="events"}"/></a>
        {/if}
                    </td>
    {/if}
                </tr>
    {/foreach}
{else}
                <tr><td colspan="7" class="emptylist">{_T string="No event has been found" domain="events"}</td></tr>
{/if}
            </tbody>
        </table>
{/block}

{block name="javascripts"}
<script type="text/javascript">
    $(function() {
        {include file="js_removal.tpl"}
    });
</script>
{/block}
