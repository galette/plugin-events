{extends file="page.tpl"}

{block name="content"}
    <form action="{path_for name="events_storebooking" data=["action" => $action, "id" => $booking->getId()]}" method="post" id="modifform">
        <div class="bigtable">
            <fieldset class="galette_form" id="general">
                <legend>{_T string="Booking informations" domain="events"}</legend>
                <div>
                <p>
                    <label for="booking_date">{_T string="Booking date" domain="events"}</label>
                    <input type="text" name="booking_date" id="booking_date" maxlength="10" size="10" value="{$booking->getDate()}" required="required"/>
                </p>
                <p>
                    <label for="event">{_T string="Event" domain="events"}</label>
{if ($login->isAdmin() or $login->isStaff()) or !$booking->getId()}
                    <select name="event" id="event" required="required">
                        <option value="0">{_T string="Select an event" domain="events"}</option>
    {foreach from=$events item=$event}
                        <option value="{$event->getId()}"{if $booking->getEventId() eq $event->getId()} selected="selected"{/if}>{$event->getName()}</option>
    {/foreach}
                    </select>
{else}
                    <input type="hidden" name="event" value="{$booking->getEventId()}"/>
                    {$booking->getEvent()->getName()}
{/if}
                </p>
                <p>
                    <span class="bline">{_T string="Member"}</span>
                    <input type="hidden" name="member" id="member" value="{$booking->getMemberId()}"/>
                    <span id="current_member">
    {if $booking->getMemberId()}
                        {$booking->getMember()->sfullname}
    {else}
                        {_T string="none" domain="events"}
    {/if}
                    </span>
    {if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <a href="#" id="choose_member">{_T string="Choose member" domain="events"}</a>
    {/if}
                </p>
                <p>
                    <label for="number_people">{_T string="Number of persons" domain="events"}</label>
                    <input type="number" name="number_people" id="number_people" value="{$booking->getNumberPeople()}" />
                </p>
                <p>
                    <label for="comment">{_T string="Comment" domain="events"}</label>
                    <textarea name="comment" id="comment">{$booking->getComment()}</textarea>
                </p>
                </div>
            </fieldset>
            <fieldset class="galette_form" id="activities">
                <legend>{_T string="Activities" domain="events"}</legend>
                <div>
    {foreach from=$booking->getEvent()->getActivities() key=aid item=activity}
                    <p>
                        <label for="activity_{$aid}">{$activity.activity->getName()}</label>
                        <input
                            type="checkbox"
                            name="activities[]"
                            id="activity_{$aid}"
                            value="{$aid}"
                            {if $booking->has($aid)} checked="checked"{/if}
                            {if $booking->getEventId() and !$booking->getEvent()->hasActivity($aid)} disabled="disabled"{/if}
                            {if $booking->getEventId() and $booking->getEvent()->isActivityRequired($aid)} required="required"{/if}
                        />
                    </p>
    {foreachelse}
                    <p>{_T string="No activity for selected event" domain="events"}</p>
    {/foreach}
                </div>
            </fieldset>
    {if $login->isAdmin() or $login->isStaff()}
            <fieldset class="galette_form" id="financial">
                <legend>{_T string="Financial informations" domain="events"}</legend>
                <div>
                <p>
                    <label for="paid">{_T string="Paid" domain="events"}</label>
                    <input type="checkbox" name="paid" id="paid"{if $booking->isPaid()} checked="checked"{/if}/>
                </p>
                <p>
                    <label for="amount">{_T string="Amount" domain="events"}</label>
                    <input type="text" name="amount" id="amount" value="{$booking->getAmount()}"/>
                </p>
                {* payment type *}
                {include file="forms_types/payment_types.tpl" current=$booking->getPaymentMethod() varname="payment_method"}
                <p>
                    <label for="bank_name">{_T string="Bank name" domain="events"}</label>
                    <input type="text" name="bank_name" id="bank_name" value="{$booking->getBankName()}" />
                </p>
                <p>
                    <label for="check_number">{_T string="Check number" domain="events"}</label>
                    <input type="text" name="check_number" id="check_number" value="{$booking->getCheckNumber()}" />
                </p>
                </div>
            </fieldset>
    {/if}
        </div>
        <div class="button-container">
            <input type="submit" name="save" value="{_T string="Save"}" />
            <input type="submit" name="cancel" value="{_T string="Cancel"}"/>
            <input type="hidden" name="id" id="id" value="{$booking->getId()}"/>
        </div>
     </form>
{/block}

{block name="javascripts"}
    <script type="text/javascript">
        $(function() {
            _collapsibleFieldsets();
            $.datepicker.setDefaults($.datepicker.regional['{$galette_lang}']);
            $('#booking_date').datepicker({
                changeMonth: true,
                changeYear: true,
                showOn: 'button',
                buttonImage: '{base_url}/{$template_subdir}images/calendar.png',
                buttonImageOnly: true,
                minDate: '-0d',
                buttonText: '{_T string="Select a date" escape="js"}',
            });
        });

        $('#event').on('change', function() {
            var _this = $(this);
            var _val = _this.find('option:selected').val()
            _this.parents('form').submit();
        });

{if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
        {* Popup for member selection *}
        $('#choose_member').click(function(){
            $.ajax({
                url: '{path_for name="ajaxMembers"}',
                type: "POST",
                data: {
                    ajax: true,
                    multiple: false,
                    from: 'single',
                    id: '{$booking->getmemberId()}'
                },
                {include file="js_loader.tpl"},
                success: function(res){
                    _members_dialog(res);
                },
                error: function() {
                    alert("{_T string="An error occured displaying members interface :(" escape="js"}");
                }
            });
            return false;
        });

        var _members_dialog = function(res){
            var _el = $('<div id="members_list" title="{_T string="Members"}"> </div>');
            _el.appendTo('#modifform').dialog({
                modal: true,
                hide: 'fold',
                width: '60%',
                height: 400,
                close: function(event, ui){
                    _el.remove();
                }
            });
            _members_ajax_mapper(res);
        }

        var _members_ajax_mapper = function(res){
            $('#members_list').append( res );
            $('#members_list tbody').find('a').each(function() {
                var _this = $(this);
                $(this).click(function(){
                    var _id = this.href.match(/.*\/(\d+)$/)[1];
                    $('#member').attr('value', _id);
                    console.log(_id);
                    console.log(_this.html())
                    $('#current_member').html(_this.html());
                    $('#members_list').dialog('close');
                    return false;
                }).attr('title', '{_T string="Click to choose this member for current booking" domain="events"}');
            });

            //Remap links
            $('#members_list .pages a').click(function(){
                $.ajax({
                    url: this.href,
                    type: "POST",
                    data: {
                        ajax: true,
                        multiple: false
                    },
                    {include file="js_loader.tpl"},
                    success: function(res){
                        $('#members_list').empty();
                        _members_ajax_mapper(res);
                    },
                    error: function() {
                        alert("{_T string="An error occured displaying members interface :(" escape="js"}");
                    }
                });
                return false;
            });
        }
 {/if}
    </script>
{/block}
