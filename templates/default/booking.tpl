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
                    <select name="event" id="event" required="required">
                        <option value="0">{_T string="Select an event"}</option>
    {foreach from=$events item=$event}
                        <option value="{$event->getId()}"{if $booking->getEventId() eq $event->getId()} selected="selected"{/if}>{$event->getName()}</option>
    {/foreach}
                    </select>
                </p>
                <p>
                    <span class="bline">{_T string="Member"}</span>
                    <input type="hidden" name="member" id="member" value="{$booking->getMemberId()}"/>
                    <span id="current_member">
    {if $booking->getMemberId()}
                        {$booking->getMember()->sfullname}
    {else}
                        {_T string="none"}
    {/if}
                    </span>
    {if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <a href="#" id="choose_member">{_T string="Choose member" domain="events"}</a>
    {/if}
                </p>
                <p>
                    <label for="meal">{_T string="Meal" domain="events"}</label>
                    <input type="checkbox" name="meal" id="meal"{if $booking->hasMeal() or $booking->getEvent()->isMealRequired()} checked="checked"{/if}{if !$booking->getEvent()->hasMeal()} disabled="disabled"{/if}{if $event->isMealRequired()} required="required"{/if}/>
                </p>
                <p>
                    <label for="lodging">{_T string="Lodging" domain="events"}</label>
                    <input type="checkbox" name="lodging" id="lodging"{if $booking->hasLodging() or $booking->getEvent()->isLodgingRequired()} checked="checked"{/if}{if !$booking->getEvent()->hasLodging()} disabled="disabled"{/if}{if $event->isLodgingRequired()} required="required"{/if}/>
                </p>
                <p>
                    <label for="number_people">{_T string="Number of persons" domain="events"}</label>
                    <input type="number" name="number_people" id="number_people" value="{$booking->getNumberPeople()}" />
                </p>
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
                    <label for="amount">{_T string="amount" domain="events"}</label>
                    <input type="text" name="amount" id="amount" value="{$booking->getAmount()}"/>
                </p>
                <p>
                    <label for="payment_method">{_T string="Payment method" domain="events"}</label>
                    <input type="text" name="payment_method" id="payment_method" value="{$booking->getPaymentMethod()}" />
                </p>
                <p>
                    <label for="bank_name">{_T string="Bank name"}</label>
                    <input type="text" name="bank_name" id="bank_name" value="{$booking->getBankName()}" />
                </p>
                <p>
                    <label for="check_number">{_T string="Check number" domain="events"}</label>
                    <input type="text" name="check_number" id="check_number" value="{$booking->getCheckNumber()}" />
                </p>
    {/if}
                </div>
            </fieldset>
        </div>
        <div class="button-container">
            <input type="submit" value="{_T string="Save"}" />
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
            $('#begin_date').datepicker({
                changeMonth: true,
                changeYear: true,
                showOn: 'button',
                buttonImage: '{base_url}/{$template_subdir}images/calendar.png',
                buttonImageOnly: true,
                minDate: '-0d',
                buttonText: '{_T string="Select a date" escape="js"}',
                onSelect: function(date) {
                    $("#end_date").datepicker("option", "minDate", $('#begin_date').datepicker('getDate'));
                }
            });
            $('#end_date').datepicker({
                changeMonth: true,
                changeYear: true,
                showOn: 'button',
                buttonImage: '{base_url}/{$template_subdir}images/calendar.png',
                buttonImageOnly: true,
                buttonText: '{_T string="Select a date" escape="js"}',
                minDate: $("#begin_date").datepicker("getDate")
            });

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
        /*$('#meal, #lodging').on('change', function() {
            var _this = $(this);
            if (!_this.is(':checked')) {
                $('#' + _this.attr('id') + '_required').prop('checked', false);
            }
        });
        $('#meal_required, #lodging_required').on('change', function() {
            var _this = $(this);
            if (_this.is(':checked')) {
                $('#' + _this.attr('id').replace(/_required/, '')).prop('checked', true);
            }
        });*/
    </script>
{/block}
