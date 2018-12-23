{extends file="page.tpl"}
{block name="content"}
        <div class="infoline">
{if $nb_events gt 0}
            {$nb_events} {if $nb_events != 1}{_T string="events" domain="events"}{else}{_T string="event" domain="events"}{/if}
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
                    <th class="actions_row">{_T string="Actions"}</th>
                </tr>
            </thead>
            <tbody>
{if $events|@count}
    {foreach from=$events item=event key=ordre}
                <tr>
                    <td class="{$rclass} right" data-scope="id">{$ordre+1+($filters->current_page - 1)*$numrows}</td>
                    <td class="{$rclass} nowrap username_row" data-scope="row">
                        {assign var="eid" value=$event->getId()}
        {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $event->getGroup()|in_array:$login->managed_groups )}
                        {*<input type="checkbox" name="event_sel[]" value="{$id}"/>*}
                        <a href="{path_for name="events_event" data=["action" => {_T string="edit" domain="routes"}, "id" => $eid]}">{$event->getName()}</a>
        {else}
                        {$event->getName()}
        {/if}
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Date" domain="events"}">{$event->getBeginDate()}</td>
                    <td class="{$rclass}" data-title="{_T string="Town" domain="events"}">{$event->getTown()}</td>
                    <td class="{$rclass}" data-title="{_T string="Group" domain="events"}">{$event->getGroupName()}</td>
                    <td class="{$rclass} center id_row tooltip {if $event->isOpen()}use{else}delete{/if}" data-title="{_T string="Open" domain="events"}">
                        <i class="fas fa-{if $event->isOpen()}unlock{else}lock{/if}"></i>
                        <span class="sr-only">
                        {if $event->isOpen()}
                            {_T string="Event is open" domain="events"}</span>
                        {else}
                            {_T string="Event is closed" domain="events"}
                        {/if}
                    </td>
                    <td class="{$rclass} center nowrap actions_row">
                        <a href="{path_for name="events_booking_export" data=["id" => $eid]}" class="tooltip">
                            <i class="fas fa-file-csv fa-fw"></i>
                            <span class="sr-only">{_T string="%eventname: export bookings as CSV" domain="events" pattern="/%eventname/" replace=$event->getName()}</span>
                        </a>
                        <a href="{path_for name="events_bookings" data=["event" => $eid]}" class="tooltip">
                            <i class="fas fa-eye fa-fw"></i>
                            <span class="sr-only">{_T string="%eventname: show bookings" domain="events" pattern="/%eventname/" replace=$event->getName()}</span>
                        </a>
    {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $event->getGroup()|in_array:$login->managed_groups )}
                        <a href="{path_for name="events_event" data=["action" => {_T string="edit" domain="routes"}, "id" => $eid]}" class="tooltip action">
                            <i class="fas fa-edit fa-fw"></i>
                            <span class="sr-only">{_T string="%eventname: edit informations" domain="events" pattern="/%eventname/" replace=$event->getName()}</span>
                        </a>
        {if $login->isAdmin() or $login->isStaff()}
                        <a class="delete tooltip" href="{path_for name="events_remove_event" data=["id" => $event->getId()]}">
                            <i class="fas fa-trash fa-fw"></i>
                            <span class="sr-only">{_T string="%eventname: remove from database" domain="events" pattern="/%eventname/" replace=$event->getName()}</span>
                        </a>
        {/if}
    {/if}
                    </td>
                </tr>
    {/foreach}
{else}
                <tr><td colspan="7" class="emptylist">{_T string="No event has been found" domain="events"}</td></tr>
{/if}
            </tbody>
        </table>
{if $nb_events gt 0}
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
