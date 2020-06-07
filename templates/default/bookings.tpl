{extends file="page.tpl"}
{block name="content"}
        <form action="{path_for name="filter-bookingslist" data=["event" => $filters->event_filter]}" method="post" id="filtre">
        <div id="listfilter">
            <label for="event_filter">{_T string="Event" domain="events"}</label>
            <select name="event_filter" id="event_filter" required="required">
                <option value="0">{_T string="Select..." domain="events"}</option>
{foreach from=$events item=$event}
                <option value="{$event->getId()}"{if $filters->event_filter eq $event->getId()} selected="selected"{/if}>{$event->getName()}</option>
{/foreach}
            </select>
            {* payment type *}
            {include file="forms_types/payment_types.tpl"
                current=$filters->payment_type_filter
                varname="payment_type_filter"
                classname=""
                label={_T string="Payment type"}
                empty=["value" => -1, "label" => {_T string="All payment types" domain="events"}]
            }

            <label for="group_filter" title="{_T string="Group" domain="events"}">{_T string="Group" domain="events"}</label>
            <select name="group_filter" id="group_filter">
                <option value="0">{_T string="Select a group"}</option>
{foreach from=$groups item=group}
                <option value="{$group->getId()}"{if $filters->group_filter eq $group->getId()} selected="selected"{/if}>{$group->getIndentName()}</option>
{/foreach}
            </select>
            <input type="submit" class="inline" value="{_T string="Filter"}"/>
            <input type="submit" name="clear_filter" class="inline" value="{_T string="Clear filter"}"/>
            <div>
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
            <a
                href="{path_for name="events_bookings" data=["event" => "all"]}"
                class="tooltip"
            >
                <i class="fas fa-eraser"></i>
                <span class="sr-only">{_T string="Show all bookings" domain="events"}</span>
            </a>
    {/if}
            <strong>{_T string="%event's bookings" domain="events" pattern="/%event/" replace=$event->getName()}</strong>
            (<a href="{path_for name="events_booking" data=["action" => "add"]}?event={$event->getId()}">{_T string="Add a new booking" domain="events"}</a>)
{/if}
{if $nb_bookings gt 0}
            -
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
                        <a href="{path_for name="events_bookings" data=["option" => "order", "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_EVENT"|constant]}">
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
                        <a href="{path_for name="events_bookings" data=["option" => "order", "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_MEMBER"|constant]}">
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
                        <a href="{path_for name="events_bookings" data=["option" => "order", "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_BOOKDATE"|constant]}">
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
                        <a href="{path_for name="events_bookings" data=["option" => "order", "event" => $eventid, "value" => "GaletteEvents\Repository\Bookings::ORDERBY_PAID"|constant]}">
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
{if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <th class="actions_row">{_T string="Actions"}</th>
{/if}
                </tr>
            </thead>
{if $nb_bookings gt 0}
            <tfoot>
                <tr>
                    <td class="right" colspan="10">
                        {_T string="Found bookings total %f" domain="events" pattern="/%f/" replace=$bookings->getSum()}
                    </td>
                </tr>
            </tfoot>
{/if}
            <tbody>
{foreach from=$bookings_list item=booking key=ordre}
    {assign var=rclass value=$booking->getRowClass()}
                <tr>
                    <td class="{$rclass} right" data-scope="id">{$ordre+1+($filters->current_page - 1)*$numrows}</td>
                    <td class="{$rclass} nowrap username_row" data-scope="row">
                        {assign var="bid" value=$booking->getId()}
        {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $booking->getEvent()->getGroup()|in_array:$login->managed_groups )}
                        <input type="checkbox" name="event_sel[]" value="{$bid}"/>
                        <a href="{path_for name="events_event" data=["action" => "edit", "id" => $booking->getEventId()]}">{$booking->getEvent()->getName()}</a>
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
                    <td class="{$rclass} tooltip center {if $booking->isPaid()}use{else}{/if}" data-title="{_T string="Paid" domain="events"}">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="sr-only">
                        {if $booking->isPaid()}
                            {_T string="Paid" domain="events"}
                        {else}
                            {_T string="Not paid" domain="events"}"
                        {/if}
                        </span>
                    </td>
                    <td class="{$rclass}" data-title="{_T string="Attendees" domain="events"}">{$booking->getNumberPeople()}</td>
    {if $login->isAdmin() or $login->isStaff() or ($login->isGroupManager() and $booking->getEvent()->getGroup()|in_array:$login->managed_groups )}
                    <td class="{$rclass} center nowrap actions_row">
                        <a href="{path_for name="events_booking" data=["action" => "edit", "id" => $bid]}" class="tooltip action">
                            <i class="fas fa-edit fa-fw"></i>
                            <span class="sr-only">{_T string="Edit booking" domain="events"}</span>
                        </a>
        {if $login->isAdmin() or $login->isStaff()}
                        <a class="delete tooltip" href="{path_for name="events_remove_booking" data=["id" => $bid]}">
                            <i class="fas fa-trash fa-fw"></i>
                            <span class="sr-only">{_T string="Remove from database" domain="events"}</span>
                        </a>
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
            <li>
                <button type="submit" id="sendmail" name="mailing">
                    <i class="fas fa-mail-bulk fa-fw"></i> {_T string="Mail"}
                </button>
            </li>
        {/if}
            <li>
                <button type="submit" id="csv" name="csv" title="{_T string="Export selected reservation members as CSV" domain="events"}">
                    <i class="fas fa-file-csv fa-fw"></i> {_T string="Members as CSV" domain="events"}
                </button>
            </li>
            <li>
                <button type="submit" id="csvbooking" name="csvbooking" title="{_T string="Export selected reservations as CSV" domain="events"}">
                    <i class="fas fa-file-csv fa-fw"></i> {_T string="Bookins as CSV" domain="events"}
                </button>
            </li>
    {/if}
            <li>
                <button type="submit" id="labels" name="labels">
                    <i class="far fa-address-card fa-fw"></i> {_T string="Generate labels"}
                </button>
            </li>
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
            $('.selection_menu *[type="submit"], .selection_menu *[type="button"]').click(function(){

                if ( this.id == 'delete' ) {
                    //mass removal is handled from 2 steps removal
                    return;
                }

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
