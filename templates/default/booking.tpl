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
                    <label for="id_adh" class="bline" >{_T string="Member"}</label>
    {if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
                    <select name="member" id="id_adh" class="nochosen">
        {if !$booking->getMemberId()}
                        <option value="">{_T string="Search for name or ID and pick member"}</option>
        {/if}
        {foreach $members.list as $k=>$v}
                            <option value="{$k}"{if $booking->getMemberId() == $k} selected="selected"{/if}>{$v}</option>
        {/foreach}
                    </select>
    {else}
        {if $booking->getMemberId()}
            {$booking->getMember()->sfullname}
        {/if}
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
            <button type="submit" class="action" name="save">
                <i class="fas fa-save fa-fw" aria-hidden="true"></i>
                {_T string="Save"}
            </button>
            <a href="{path_for name="events_bookings" data=["event" => "all"]}" class="button">
                <i class="fas fa-th-list fa-fw" aria-hidden="true"></i>
                {_T string="Cancel"}
            </a>
            <input type="hidden" name="id" id="id" value="{$booking->getId()}"/>
        </div>
     </form>
{/block}

{block name="javascripts"}
    <script type="text/javascript">
        {include file="js_chosen_adh.tpl"}
        $(function() {
            _collapsibleFieldsets();
            $.datepicker.setDefaults($.datepicker.regional['{$galette_lang}']);
            $('#booking_date').datepicker({
                changeMonth: true,
                changeYear: true,
                showOn: 'button',
                minDate: '-0d',
                buttonText: '<i class="far fa-calendar-alt"></i> <span class="sr-only">{_T string="Select a date" escape="js"}</span>'
            });
        });

        $('#event').on('change', function() {
            var _this = $(this);
            var _val = _this.find('option:selected').val()
            _this.parents('form').submit();
        });
    </script>
{/block}
