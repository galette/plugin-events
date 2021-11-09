{extends file="page.tpl"}

{block name="content"}
    <form action="{if $activity->getId()}{path_for name="events_storeactivity_edit" data=["id" => $activity->getId()]}{else}{path_for name="events_storeactivity_add"}{/if}" method="post">
        <div class="bigtable">
            <fieldset class="galette_form" id="general">
                <legend>{_T string="General informations" domain="events"}</legend>
                <div>
                <p>
                    <label for="active">{_T string="Is active" domain="events"}</label>
                    <input type="checkbox" name="active" id="active"{if $activity->isActive()} checked="checked"{/if}/>
                </p>
                <p>
                    <label for="name">{_T string="Name" domain="events"}</label>
                    <input type="text" name="name" id="name" value="{$activity->getName()}" required="required"/>
                </p>
                <p>
                    <label for="comment">{_T string="Comment" domain="events"}</label>
                    <textarea name="comment" id="comment">{$activity->getComment()}</textarea>
                </p>
                </div>
            </fieldset>
            {*<fieldset class="galette_form" id="activities">
                <legend>{_T string="Related activities" domain="events"}</legend>
                <div>
                <p>
                    <label for="noon_meal">{_T string="Noon meal" domain="events"}</label>
                    <select name="noon_meal" id="noon_meal">
                        <option value="{GaletteEvents\Event::ACTIVITY_YES}"{if $event->getNoonMeal() eq GaletteEvents\Event::ACTIVITY_YES} selected="selected"{/if}>{_T string="Yes"}</option>
                        <option value="{GaletteEvents\Event::ACTIVITY_NO}"{if $event->getNoonMeal() eq GaletteEvents\Event::ACTIVITY_NO} selected="selected"{/if}>{_T string="No"}</option>
                        <option value="{GaletteEvents\Event::ACTIVITY_REQUIRED}"{if $event->getNoonMeal() eq GaletteEvents\Event::ACTIVITY_REQUIRED} selected="selected"{/if}>{_T string="Required" domain="events"}</option>
                    </select>
                </p>
                <p>
                    <label for="even_meal">{_T string="Even meal" domain="events"}</label>
                    <select name="even_meal" id="even_meal">
                        <option value="{GaletteEvents\Event::ACTIVITY_YES}"{if $event->getEvenMeal() eq GaletteEvents\Event::ACTIVITY_YES} selected="selected"{/if}>{_T string="Yes"}</option>
                        <option value="{GaletteEvents\Event::ACTIVITY_NO}"{if $event->getEvenMeal() eq GaletteEvents\Event::ACTIVITY_NO} selected="selected"{/if}>{_T string="No"}</option>
                        <option value="{GaletteEvents\Event::ACTIVITY_REQUIRED}"{if $event->getEvenMeal() eq GaletteEvents\Event::ACTIVITY_REQUIRED} selected="selected"{/if}>{_T string="Required" domain="events"}</option>
                    </select>
                </p>
                <p>
                    <label for="lodging">{_T string="Lodging" domain="events"}</label>
                    <select name="lodging" id="lodging">
                        <option value="{GaletteEvents\Event::ACTIVITY_YES}"{if $event->getLodging() eq GaletteEvents\Event::ACTIVITY_YES} selected="selected"{/if}>{_T string="Yes"}</option>
                        <option value="{GaletteEvents\Event::ACTIVITY_NO}"{if $event->getLodging() eq GaletteEvents\Event::ACTIVITY_NO} selected="selected"{/if}>{_T string="No"}</option>
                        <option value="{GaletteEvents\Event::ACTIVITY_REQUIRED}"{if $event->getLodging() eq GaletteEvents\Event::ACTIVITY_REQUIRED} selected="selected"{/if}>{_T string="Required" domain="events"}</option>
                    </select>
                </p>
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
            </fieldset>*}
        </div>
        <div class="button-container">
            <button type="submit" class="action">
                <i class="fas fa-save fa-fw" aria-hidden="true"></i>
                {_T string="Save"}
            </button>
            <a href="{path_for name="events_activities"}" class="button">
                <i class="fas fa-th-list fa-fw" aria-hidden="true"></i>
                {_T string="Cancel"}
            </a>
            <input type="hidden" name="id" id="id" value="{$activity->getId()}"/>
            {include file="forms_types/csrf.tpl"}
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

        $('#meal, #lodging').on('change', function() {
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
        });
    </script>
{/block}
