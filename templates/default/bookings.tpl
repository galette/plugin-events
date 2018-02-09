{extends file="page.tpl"}
{block name="content"}
        <form action="{path_for name="filter-bookingslist"}" method="post" id="filtre">
        <div id="listfilter">
            {* payment type *}
            {include file="forms_types/payment_types.tpl"
                current=$filters->payment_type_filter
                varname="payment_type_filter"
                classname=""
                empty=["value" => -1, "label" => {_T string="All" domain="events"}]
            }
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
            <strong>{_T string="%event's bookings" pattern="/%event/" replace=$event->getName() domain="events"}</strong>
            (<a href="{path_for name="events_booking" data=["action" => {_T string="add" domain="routes"}]}?event={$event->getId()}">{_T string="Add a new booking" domain="events"}</a>)
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
        </form>
        <form action="{path_for name="batch-eventslist"}" method="post" id="listform">
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
    {foreach from=\GaletteEvents\Event::getActivities() key=activity item=label}
                    <th class="left id_row">{$label}</th>
    {/foreach}
{if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <th class="actions_row">{_T string="Actions"}</th>
{/if}
                </tr>
            </thead>
{if $nb_bookings gt 0}
            <tfoot>
                <tr>
                    <td class="right" colspan="10">
                        {_T string="Found bookings total %f" pattern="/%f/" replace=$bookings->getSum() domain="events"}
                    </td>
                </tr>
            </tfoot>
{/if}
            <tbody>
{foreach from=$bookings_list item=booking key=ordre}
                <tr>
                    <td class="{$rclass} right" data-scope="id">{$ordre+1+($filters->current_page - 1)*$numrows}</td>
                    <td class="{$rclass} nowrap username_row" data-scope="row">
                        {assign var="bid" value=$booking->getId()}
        {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $booking->getEvent()->getGroup()|in_array:$login->managed_groups )}
                        <input type="checkbox" name="event_sel[]" value="{$id}"/>
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
    {foreach from=$booking->getEvent()->getActivities() key=activity item=label}
                    <td class="{$rclass}" data-title="$label">
                        {if $booking->has($activity)}
                            <img src="{base_url}/{$template_subdir}images/icon-on.png" alt="{_T string="Has %activity" pattern="/%activity/" replace=$activity domain="events"}"/>
                        {else}
                            <img src="{base_url}/{$template_subdir}images/icon-off.png" alt="{_T string="No %activity" pattern="/%activity/" replace=$activity domain="events"}"/>
                        {/if}
                    </td>

    {/foreach}
    {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $booking->getEvent()->getGroup()|in_array:$login->managed_groups )}
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
{if $nb_bookings gt 0}
        <div class="center cright">
            {_T string="Pages:"}<br/>
            <ul class="pages">{$pagination}</ul>
        </div>

        <ul class="selection_menu">
            <li>{_T string="For the selection:"}</li>
    {if $login->isAdmin() or $login->isStaff()}
        {if $pref_mail_method neq constant('Galette\Core\GaletteMail::METHOD_DISABLED')}
            <li><input type="submit" id="sendmail" name="mailing" value="{_T string="Mail"}"/></li>
        {/if}
            <li><input type="submit" name="csv" value="{_T string="Export as CSV"}"/></li>
    {/if}
        </ul>
        </form>
{/if}
{/block}

{block name="javascripts"}
<script type="text/javascript">
{if $bookings_list|@count}
        var _checkselection = function() {
            var _checkeds = $('table.listing').find('input[type=checkbox]:checked').length;
            if ( _checkeds == 0 ) {
                var _el = $('<div id="pleaseselect" title="{_T string="No booking selected" escape="js" domain="events"}">{_T string="Please make sure to select at least one booking from the list to perform this action." escape="js" domain="events"}</div>');
                _el.appendTo('body').dialog({
                    modal: true,
                    buttons: {
                        Ok: function() {
                            $(this).dialog( "close" );
                        }
                    },
                    close: function(event, ui){
                        _el.remove();
                    }
                });
                return false;
            }
            return true;
        }
{/if}
    $(function() {
        {include file="js_removal.tpl"}
{if $bookings_list|@count}
            var _checklinks = '<div class="checkboxes"><span class="fleft"><a href="#" class="checkall">{_T string="(Un)Check all"}</a> | <a href="#" class="checkinvert">{_T string="Invert selection"}</a></span></div>';
            $('.listing').before(_checklinks);
            $('.listing tfoot td').prepend(_checklinks);
            _bind_check('event_sel');
            $('#nbshow').change(function() {
                this.form.submit();
            });
            $('.selection_menu input[type="submit"], .selection_menu input[type="button"]').click(function(){

                /*if ( this.id == 'delete' ) {
                    //mass removal is handled from 2 steps removal
                    return;
                }*/

                if (!_checkselection()) {
                    return false;
                } else {
    {if $existing_mailing eq true}
                    if (this.id == 'sendmail') {
                        var _el = $('<div id="existing_mailing" title="{_T string="Existing mailing"}">{_T string="A mailing already exists. Do you want to create a new one or resume the existing?"}</div>');
                        _el.appendTo('body').dialog({
                            modal: true,
                            hide: 'fold',
                            width: '25em',
                            height: 150,
                            close: function(event, ui){
                                _el.remove();
                            },
                            buttons: {
                                '{_T string="Resume"}': function() {
                                    $(this).dialog( "close" );
                                    location.href = '{path_for name="mailing"}';
                                },
                                '{_T string="New"}': function() {
                                    $(this).dialog( "close" );
                                    //add required controls to the form, change its action URI, and send it.
                                    var _form = $('#listform');
                                    _form.append($('<input type="hidden" name="mailing_new" value="true"/>'));
                                    _form.append($('<input type="hidden" name="mailing" value="true"/>'));
                                    _form.submit();
                                }
                            }
                        });
                        return false;
                    }
    {/if}
                    return true;
                }
            });
{/if}
    });
</script>
{/block}
