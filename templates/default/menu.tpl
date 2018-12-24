        <h1 class="nojs">{_T string="Events" domain="events"}</h1>
        <ul>
            <li{if $cur_route eq "events_events"} class="selected"{/if}><a href="{path_for name="events_events"}">{_T string="Events" domain="events"}</a></li>
{if $login->isAdmin() or $login->isStaff() or $login->isGroupManager()}
            <li{if $cur_route eq "events_event"} class="selected"{/if}><a href="{path_for name="events_event" data=["action" => "add"]}">{_T string="New event" domain="events"}</a></li>
{/if}
            <li{if $cur_route eq "events_bookings"} class="selected"{/if}><a href="{path_for name="events_bookings" data=["event" => "all"]}">{_T string="Bookings" domain="events"}</a></li>
            <li{if $cur_route eq "events_booking"} class="selected"{/if}><a href="{path_for name="events_booking" data=["action" => "add"]}">{_T string="New booking" domain="events"}</a></li>
{if $login->isAdmin() or $login->isStaff()}
            <li{if $cur_route eq "events_activities"} class="selected"{/if}><a href="{path_for name="events_activities"}">{_T string="Activities" domain="events"}</a></li>
            <li{if $cur_route eq "events_activity"} class="selected"{/if}><a href="{path_for name="events_activity" data=["action" => "add"]}">{_T string="New activity" domain="events"}</a></li>
{/if}
        </ul>
