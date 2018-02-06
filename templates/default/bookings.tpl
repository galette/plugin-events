{extends file="page.tpl"}
{block name="content"}
        <form action="{path_for name="filter-bookingslist"}" method="post" id="filtre">
        <div id="listfilter">
            {* payment type *}
            {include file="forms_types/payment_types.tpl" current=$filters->payment_type_filter varname="payment_type_filter" classname=""}
            <input type="submit" class="inline" value="{_T string="Filter"}"/>
            <input type="submit" name="clear_filter" class="inline" value="{_T string="Clear filter"}"/>
            <div/>
                {_T string="Paid bookings:" domain="events"}
                <input type="radio" name="paid_filter" id="filter_dc_paid" value="{GaletteEvents\Repository\Bookings::FILTER_DC_PAID}"{if $filters->paid_filter eq constant('GaletteEvents\Repository\Bookings::FILTER_DC_PAID')} checked="checked"{/if}>
                <label for="filter_dc_paid" >{_T string="Don't care"}</label>
                <input type="radio" name="paid_filter" id="filter_paid" value="{GaletteEvents\Repository\Bookings::FILTER_PAID}"{if $filters->paid_filter eq constant('GaletteEvents\Repository\Bookings::FILTER_PAID')} checked="checked"{/if}>
                <label for="filter_paid" >{_T string="Paid" domain="events"}</label>
                <input type="radio" name="paid_filter" id="filter_not_paid" value="{GaletteEvents\Repository\Bookings::FILTER_NOT_PAID}"{if $filters->paid_filter eq constant('GaletteEvents\Repository\Bookings::FILTER_NOT_PAID')} checked="checked"{/if}>
                <label for="filter_not_paid" >{_T string="Not paid" domain="events"}</label>
            </div>
        </div>
        <div class="infoline">
{if $event}
    {if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
            <a id="clearfilter" href="{path_for name="events_bookings" data=["event" => {_T string="all" domain="events_routes" notrans="true"}]}" title="{_T string="Show all bookings" domain="events"}">{_T string="Show all bookings" domain="events"}</a>
    {/if}
            <strong>{_T string="%event's bookings" pattern="/%event/" replace=$event->getName()}</strong>
            (<a href="{path_for name="events_booking" data=["action" => {_T string="add" domain="routes"}]}?event={$event->getId()}">{_T string="Add a new booking"}</a>)
{/if}
{if $nb_bookings gt 0}
            {$nb_bookings} {if $nb_bookings != 1}{_T string="bookings" domain="events"}{else}{_T string="booking" domain="events"}{/if}
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
                        <a href="{path_for name="events_bookings" data=["option" => {_T string="order" domain="routes"}, "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_EVENT"|constant]}">
                            {_T string="Event" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Bookings::ORDERBY_EVENT')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\BookingsList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left">
                        <a href="{path_for name="events_bookings" data=["option" => {_T string="order" domain="routes"}, "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_MEMBER"|constant]}">
                            {_T string="Member"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Bookings::ORDERBY_MEMBER')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\BookingsList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left date_row">
                        <a href="{path_for name="events_bookings" data=["option" => {_T string="order" domain="routes"}, "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_BOOKDATE"|constant]}">
                            {_T string="Booking date" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Bookings::ORDERBY_BOOKDATE')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\BookingsList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left id_row">
                        <a href="{path_for name="events_bookings" data=["option" => {_T string="order" domain="routes"}, "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_PAID"|constant]}">
                            {_T string="Paid" domain="events"}
                            {if $filters->orderby eq constant('GaletteEvents\Repository\Bookings::ORDERBY_PAID')}
                                {if $filters->ordered eq constant('GaletteEvents\Filters\BookingsList::ORDER_ASC')}
                                    <img src="{base_url}/{$template_subdir}images/down.png" width="10" height="6" alt=""/>
                                {else}
                                    <img src="{base_url}/{$template_subdir}images/up.png" width="10" height="6" alt=""/>
                                {/if}
                            {/if}
                        </a>
                    </th>
                    <th class="left id_row">{_T string="Attendees" domain="events"}</th>
                    <th class="left id_row">{_T string="Meal" domain="events"}</th>
                    <th class="left id_row">{_T string="Lodging" domain="events"}</th>
{if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <th class="actions_row">{_T string="Actions"}</th>
{/if}
                </tr>
            </thead>
{if $nb_bookings != 0}
            <tfoot>
                <tr>
                    <td class="right" colspan="8">
                        {_T string="Found bookings total %f" pattern="/%f/" replace=$bookings->getSum()}
                    </td>
                </tr>
            </tfoot>
{/if}
            <tbody>
{foreach from=$bookings->getList() item=booking key=ordre}
                <tr>
                    <td class="{$rclass} right" data-scope="id">{$ordre+1+($filters->current_page - 1)*$numrows}</td>
                    <td class="{$rclass} nowrap username_row" data-scope="row">
                        {assign var="bid" value=$booking->getId()}
        {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $event->getGroup()|in_array:$login->managed_groups )}
                        {*<input type="checkbox" name="event_sel[]" value="{$id}"/>*}
                        <a href="{path_for name="events_event" data=["action" => {_T string="edit" domain="routes"}, "id" => $booking->getEventId()]}">{$booking->getEvent()->getName()}</a>
        {else}
                        {$booking->getEvent()->getName()}
        {/if}
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Member"}">
                        <a href="{path_for name="member" data=["id" => $booking->getMemberId()]}">
                            {$booking->getMember()->sfullname}
                        </a>
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Booking date" domain="events"}">{$booking->getDate()}</td>
                    <td class="{$rclass}" data-title="{_T string="Paid" domain="events"}">
                        {if $booking->isPaid()}
                            <img src="{base_url}/{$template_subdir}images/icon-on.png" alt="{_T string="Paid" domain="events"}" title="{_T string="Booking has been paid" domain="events"}"/>
                        {else}
                            <img src="{base_url}/{$template_subdir}images/icon-off.png" alt="{_T string="Not paid"}" title="{_T string="Booking has not been paid" domain="events"}"/>
                        {/if}
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Attendees" domain="events"}">{$booking->getNumberPeople()}</td>
                    <td class="{$rclass}" data-title="{_T string="Meal" domain="events"}">
                        {if $booking->hasMeal()}
                            <img src="{base_url}/{$template_subdir}images/icon-on.png" alt="{_T string="Has meal" domain="events"}"/>
                        {else}
                            <img src="{base_url}/{$template_subdir}images/icon-off.png" alt="{_T string="No meal"}"/>
                        {/if}
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Lodging" domain="events"}">
                        {if $booking->hasLodging()}
                            <img src="{base_url}/{$template_subdir}images/icon-on.png" alt="{_T string="Lodging" domain="events"}"/>
                        {else}
                            <img src="{base_url}/{$template_subdir}images/icon-off.png" alt="{_T string="No lodging"}"/>
                        {/if}
                    </td>
    {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $event->getGroup()|in_array:$login->managed_groups )}
                    <td class="{$rclass} center nowrap actions_row">
                        <a href="{path_for name="events_booking" data=["action" => {_T string="edit" domain="routes"}, "id" => $bid]}"><img src="{base_url}/{$template_subdir}images/icon-edit.png" alt="{_T string="[mod]"}" width="16" height="16" title="{_T string="Edit booking" domain="events"}"/></a>
        {if $login->isAdmin() or $login->isStaff()}
                        <a class="delete" href="{path_for name="events_remove_booking" data=["id" => $bid]}"><img src="{base_url}/{$template_subdir}images/icon-trash.png" alt="{_T string="[del]"}" width="16" height="16" title="{_T string="Remove from database" domain="events"}"/></a>
        {/if}
                    </td>
    {/if}
                </tr>
{foreachelse}
                <tr><td colspan="9" class="emptylist">{_T string="No booking has been found" domain="events"}</td></tr>
{/foreach}
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
