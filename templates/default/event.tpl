{extends file="page.tpl"}

{block name="content"}
    <form action="{path_for name="events_storeevent" data=["action" => $action, "id" => $event->getId()]}" method="post">
        <div class="bigtable">
            <fieldset class="galette_form" id="general">
                <legend>{_T string="General informations" domain="events"}</legend>
                <div>
                <p>
                    <label for="open">{_T string="Is open" domain="events"}</label>
                    <input type="checkbox" name="open" id="open"{if $event->isOpen()} checked="checked"{/if}/>
                    <span class="exemple">{_T string="(event will be considered as closed when begin date has been exceeded)" domain="events"}</span>
                </p>
                <p>
                    <label for="name">{_T string="Name" domain="events"}</label>
                    <input type="text" name="name" id="name" value="{$event->getName()}" required="required"/>
                </p>
                <p>
                    <label for="begin_date">{_T string="Begin date" domain="events"}</label>
                    <input type="text" name="begin_date" id="begin_date" maxlength="10" size="10" value="{$event->getBeginDate()}" required="required"/>
                </p>
                <p>
                    <label for="end_date">{_T string="End date" domain="events"}</label>
                    <input type="text" name="end_date" id="end_date" maxlength="10" size="10" value="{$event->getEndDate()}" />
                </p>
                <p>
                    <label class="tooltip" for="group" title="{_T string="Restrict event to selected group (and its subgroups)." domain="events"}">{_T string="Limit to group" domain="events"}</label>
                    <span class="tip">{_T string="Restrict event to selected group (and its subgroups)." domain="events"}</span>
                    <select name="group" id="group">
                        <option value="0">{_T string="Select a group"}</option>
    {foreach from=$groups item=group}
                        <option value="{$group->getId()}"{if $event->getGroup() eq $group->getId()} selected="selected"{/if}>{$group->getIndentName()}</option>
    {/foreach}
                    </select>
                </p>
                <p>
                    <label for="comment">{_T string="Comment" domain="events"}</label>
                    <textarea name="comment" id="comment">{$event->getComment()}</textarea>
                </p>
                </div>
            </fieldset>
            <fieldset class="galette_form" id="activities">
                <legend>{_T string="Activities" domain="events"}</legend>
                <div>
                <p class="right">
    {assign var=availables value=$event->availableActivities()}
    {assign var=activities value=$event->getActivities()}

    {if $availables|@count}
                    <select name="attach_activity">
                        <option value="">{_T string="Choose an activity to add" domain="events"}</option>
        {foreach from=$availables item=activity}
                        <option value="{$activity.id_activity}">{$activity.name}</option>
        {/foreach}
        </select>
        <button type="submit" class="tooltip" name="add_activity">
            <i class="fas fa-plus-circle"></i>
            <span class="sr-only">{_T string="Add selected activity to event" domain="events"}"</span>
        </button>
    {/if}
    {if $activities|@count}
                    <select name="detach_activity">
                        <option value="">{_T string="Choose an activity to remove" domain="events"}</option>
        {foreach from=$activities item=item}
            {assign var=activity value=$item.activity}
                        <option value="{$activity->getId()}">{$activity->getName()}</option>
        {/foreach}
        </select>
        <button type="submit" class="button delete tooltip" name="remove_activity">
            <i class="fas fa-trash"></i>
            <span class="sr-only">{_T string="Remove selected activity from event" domain="events"}</span>
        </button>
    {/if}
                </p>
    {if $activities|@count}
        {foreach from=$activities item=item}
            {assign var=activity value=$item.activity}
                <p>
                    <input type="hidden" name="activities_ids[]" value="{$activity->getId()}"/>
                    <label for="activities_status_{$activity->getId()}">{$activity->getName()}</label>
                    <select name="activities_status[]" id="activities_status_{$activity->getId()}">
                        <option value="{GaletteEvents\Activity::YES}"{if $item.status eq GaletteEvents\Activity::YES} selected="selected"{/if}>{_T string="Yes"}</option>
                        <option value="{GaletteEvents\Activity::NO}"{if $item.status eq GaletteEvents\Activity::NO} selected="selected"{/if}>{_T string="No"}</option>
                        <option value="{GaletteEvents\Activity::REQUIRED}"{if $item.status eq GaletteEvents\Activity::REQUIRED} selected="selected"{/if}>{_T string="Required" domain="events"}</option>
                    </select>
                </p>
        {/foreach}
    {/if}
                </div>
            </fieldset>
            <fieldset class="galette_form" id="location">
                <legend>{_T string="Location" domain="events"}</legend>
                <div>
                <p>
                    <label for="address">{_T string="Address" domain="events"}</label>
                    <input type="text" name="address" id="address" class="large" value="{$event->getAddress()}"/>
                </p>
                <p>
                    <label for="zip">{_T string="Zip code" domain="events"}</label>
                    <input type="text" name="zip" id="zip" value="{$event->getZip()}"/>
                </p>
                <p>
                    <label for="town">{_T string="Town" domain="events"}</label>
                    <input type="text" name="town" id="town" class="town" value="{$event->getTown()}" required="required"/>
                </p>
                <p>
                    <label for="country">{_T string="Country" domain="events"}</label>
                    <input type="text" name="country" id="country" class="country" value="{$event->getCountry()}"/>
                </p>
                </div>
            </fieldset>
        </div>
        <div class="button-container">
            <button type="submit" class="action" name="save">
                <i class="fas fa-save fa-fw" aria-hidden="true"></i>
                {_T string="Save"}
            </button>
            <a href="{path_for name="events_activities"}" class="button">
                <i class="fas fa-th-list fa-fw" aria-hidden="true"></i>
                {_T string="Cancel"}
            </a>
            <input type="hidden" name="id" id="id" value="{$event->getId()}"/>
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
                minDate: '-0d',
                buttonText: '<i class="far fa-calendar-alt"></i> <span class="sr-only">{_T string="Select a date" escape="js"}</span>',
                onSelect: function(date) {
                    $("#end_date").datepicker("option", "minDate", $('#begin_date').datepicker('getDate'));
                }
            });
            $('#end_date').datepicker({
                changeMonth: true,
                changeYear: true,
                showOn: 'button',
                buttonText: '<i class="far fa-calendar-alt"></i> <span class="sr-only">{_T string="Select a date" escape="js"}</span>',
                minDate: $("#begin_date").datepicker("getDate")
            });

        });
    </script>
{/block}
