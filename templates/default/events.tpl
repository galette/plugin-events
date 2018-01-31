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
            {$nb_events} {if $nb_events != 1}{_T string="events" domain="events"}{else}{_T string="event" domain="event"}{/if}
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
                    <th class="actions_row">{_T string="Actions"}</th>
                </tr>
            </thead>
            <tbody>
{if $events|@count}
    {foreach from=events item=event key=ordre}
        {*assign var=rclass value=$member->getRowClass() *}
                <tr>
                    <td class="{$rclass} right" data-scope="id">{$ordre+1+($filters->current_page - 1)*$numrows}</td>
                    <td class="{$rclass} nowrap username_row" data-scope="row">
                        <input type="checkbox" name="event_sel[]" value="{$event->id}"/>
                        {assign var="eid" value=$event->id}
                        <a href="{path_for name="events_events" data=["id" => $event->id]}">{$event->getname()}</a>
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Date" domain="events"}">{$event->getBeginDate()}</td>
                    <td class="{$rclass} center nowrap actions_row">
                        {*<a href="{path_for name="editmember" data=["action" => {_T string="edit" domain="routes"}, "id" => $mid]}"><img src="{base_url}/{$template_subdir}images/icon-edit.png" alt="{_T string="[mod]"}" width="16" height="16" title="{_T string="%membername: edit informations" pattern="/%membername/" replace=$member->sname}"/></a>
    {if $login->isAdmin() or $login->isStaff()}
                        <a href="{path_for name="contributions" data=["type" => {_T string="contributions" domain="routes"}, "option" => {_T string="member" domain="routes"}, "value" => $member->id]}"><img src="{base_url}/{$template_subdir}images/icon-money.png" alt="{_T string="[$]"}" width="16" height="16" title="{_T string="%membername: contributions" pattern="/%membername/" replace=$member->sname}"/></a>
                        <a class="delete" href="{path_for name="removeMember" data=["id" => $member->id]}"><img src="{base_url}/{$template_subdir}images/icon-trash.png" alt="{_T string="[del]"}" width="16" height="16" title="{_T string="%membername: remove from database" pattern="/%membername/" replace=$member->sname}"/></a>
    {/if}
    {if $login->isSuperAdmin()}
                        <a href="{path_for name="impersonate" data=["id" => $mid]}"><img src="{base_url}/{$template_subdir}images/icon-impersonate.png" alt="{_T string="Impersonate"}" width="16" height="16" title="{_T string="Log in in as %membername" pattern="/%membername/" replace=$member->sname}"/></a>
    {/if}*}
                    </td>
                </tr>
    {/foreach}
{else}
                <tr><td colspan="7" class="emptylist">{_T string="No event has been found"}</td></tr>
{/if}
            </tbody>
        </table>
{/block}
