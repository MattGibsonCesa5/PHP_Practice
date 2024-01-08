<?php
// we want the navbar to be in this page so we include it here
include_once("header.php");
include_once("ajax/calendar/getCalendarEventTypes.php ");

?>

<script>




  function getEvents() {
    // send the AJAX request to update the setting
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "ajax/calendar/getEvents.php", true);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        // console.log(this.responseText);
      }
    };
    xmlhttp.send();
  }


function getEventTypes() {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.open("GET", "ajax/calendar/getCalendarEventTypes.php", true);
  xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xmlhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      // console.log(this.responseText);
    }
  };
  xmlhttp.send();
}
getEventTypes();
  getEvents();

  var eventTypes = <?php echo json_encode($eventTypes); ?>;


  var EVENT_CATEGORIES = ['milestone', 'task'];

function generateRandomEvent(eventType, renderStart, renderEnd) {
  function generateTime(event, renderStart, renderEnd) {
    var startDate = moment(renderStart.getTime());
    var endDate = moment(renderEnd.getTime());
    var diffDate = endDate.diff(startDate, 'days');

    event.isAllday = chance.bool({ likelihood: 30 });
    if (event.isAllday) {
      event.category = 'allday';
    } else if (chance.bool({ likelihood: 30 })) {
      event.category = EVENT_CATEGORIES[chance.integer({ min: 0, max: 1 })];
      if (event.category === EVENT_CATEGORIES[1]) {
        event.dueDateClass = 'morning';
      }
    } else {
      event.category = 'time';
    }

    startDate.add(chance.integer({ min: 0, max: diffDate }), 'days');
    startDate.hours(chance.integer({ min: 0, max: 23 }));
    startDate.minutes(chance.bool() ? 0 : 30);
    event.start = startDate.toDate();

    endDate = moment(startDate);
    if (event.isAllday) {
      endDate.add(chance.integer({ min: 0, max: 3 }), 'days');
    }

    event.end = endDate.add(chance.integer({ min: 1, max: 4 }), 'hour').toDate();

    if (!event.isAllday && chance.bool({ likelihood: 20 })) {
      event.goingDuration = chance.integer({ min: 30, max: 120 });
      event.comingDuration = chance.integer({ min: 30, max: 120 });

      if (chance.bool({ likelihood: 50 })) {
        event.end = event.start;
      }
    }
  }

  function generateNames() {
    var names = [];
    var i = 0;
    var length = chance.integer({ min: 1, max: 10 });

    for (; i < length; i += 1) {
      names.push(chance.name());
    }

    return names;
  }

  var id = chance.guid();
  var calendarId = eventType.id;
  var title = chance.sentence({ words: 3 });
  var body = chance.bool({ likelihood: 20 }) ? chance.sentence({ words: 10 }) : '';
  var isReadOnly = chance.bool({ likelihood: 20 });
  var isPrivate = chance.bool({ likelihood: 20 });
  var location = chance.address();
  var attendees = chance.bool({ likelihood: 70 }) ? generateNames() : [];
  var recurrenceRule = '';
  var state = chance.bool({ likelihood: 50 }) ? 'Busy' : 'Free';
  var goingDuration = chance.bool({likelihood: 20}) ? chance.integer({ min: 30, max: 120 }) : 0;
  var comingDuration = chance.bool({likelihood: 20}) ? chance.integer({ min: 30, max: 120 }) : 0;
  var raw = {
    memo: chance.sentence(),
    creator: {
      name: chance.name(),
      avatar: chance.avatar(),
      email: chance.email(),
      phone: chance.phone(),
    },
  };

  var event = {
    id: id,
    calendarId: calendarId,
    title: title,
    body: body,
    isReadOnly: isReadOnly,
    isPrivate: isPrivate,
    location: location,
    attendees: attendees,
    recurrenceRule: recurrenceRule,
    state: state,
    goingDuration: goingDuration,
    comingDuration: comingDuration,
    raw: raw,
  }

  generateTime(event, renderStart, renderEnd);

  if (event.category === 'milestone') {
    event.color = '#000'
    event.backgroundColor = 'transparent';
    event.borderColor = 'transparent';
    event.dragBackgroundColor = 'transparent';
  }

  return event;
}

function generateRandomEvents(viewName, renderStart, renderEnd) {
  var i, j;
  var event, duplicateEvent;
  var events = [];

eventTypes.forEach(function(eventType) {
    for (i = 0; i < chance.integer({ min: 20, max: 50 }); i += 1) {
      event = generateRandomEvent(eventType, renderStart, renderEnd);
      events.push(event);

      if (i % 5 === 0) {
        for (j = 0; j < chance.integer({min: 0, max: 2}); j+= 1) {
          duplicateEvent = JSON.parse(JSON.stringify(event));
          duplicateEvent.id += `-${j}`;
          duplicateEvent.calendarId = chance.integer({min: 1, max: 5}).toString();
          duplicateEvent.goingDuration = 30 * chance.integer({min: 0, max: 4});
          duplicateEvent.comingDuration = 30 * chance.integer({min: 0, max: 4});
          events.push(duplicateEvent);
        }
      }
    }
  });

  return events;
}
var appState = {
  activeCalendarIds: eventTypes.map(function (eventType) { 
    return eventType.id;
  }),
  isDropdownActive: false,
};

function setCheckboxBackgroundColor(checkbox) {
  var calendarId = checkbox.value; // Retrieves the value associated with the checkbox, representing the calendar ID.

  // Selects the label element immediately following the checkbox.
  // This label is assumed to be related to the checkbox visually.
  var label = checkbox.nextElementSibling;

  // Attempts to find the calendar's information from eventTypes using the calendar ID.
  // eventTypes is assumed to be an array of calendar objects with id and backgroundColor properties.
  var calendarInfo = eventTypes.find(function (eventType) {
      return eventType.id === calendarId;
  });

  // Sets a default background color if no specific calendar info is found.
  // This ensures there is always a visual indicator even if the data is missing or incorrect.
  if (!calendarInfo) {
      calendarInfo = { backgroundColor: '#2a4fa7' };
  }

  // Applies the background color to the label using a CSS custom property.
  // The color changes depending on whether the checkbox is checked or not,
  // providing a visual cue about the checkbox state.
  label.style.setProperty(
      '--checkbox-' + calendarId,
      checkbox.checked ? calendarInfo.backgroundColor : '#fff'
  );
}

// Function to bind application-wide events
function bindAppEvents() {
// Attaches an event listener to the dropdown trigger for handling clicks.
// When clicked, it will toggle the visibility state of the dropdown menu.
dropdownTrigger.addEventListener('click', toggleDropdownState);

// Attaches an event listener to the 'previous' button.
// On click, it navigates the calendar to the previous period (e.g., previous month, week, or day)
// and updates the view to reflect this change.
prevButton.addEventListener('click', function () {
    cal.prev(); // Moves the calendar to the previous period.
    update(); // Calls the update function to refresh the calendar display.
});

// Attaches an event listener to the 'next' button.
// This listener allows navigation to the next calendar period and updates the view accordingly.
nextButton.addEventListener('click', function () {
    cal.next(); // Moves the calendar to the next period.
    update(); // Updates the calendar display to show the new period.
});

// Attaches an event listener to the 'today' button.
// Clicking this button will reset the calendar view to the current date.
todayButton.addEventListener('click', function () {
    cal.today(); // Sets the calendar view to the current date.
    update(); // Updates the calendar display.
});

// Event listener for handling clicks within the dropdown content.
// This is typically used for changing the calendar view (e.g., month, week, day).
dropdownContent.addEventListener('click', function (e) {
    var targetViewName;

    // Checks if the clicked element has a 'viewName' data attribute.
    // If so, it changes the calendar view to the specified one.
    if ('viewName' in e.target.dataset) {
        targetViewName = e.target.dataset.viewName;
        cal.changeView(targetViewName); // Changes the calendar to the selected view.
        checkboxCollapse.disabled = targetViewName === 'month'; // Disables the checkbox if the month view is selected.
        toggleDropdownState(); // Toggles the dropdown's open/close state.
        update(); // Updates the calendar view.
    }
});

// Event listener for the checkbox that collapses duplicate events in week view.
// It dynamically adjusts the calendar's options based on the checkbox's state.
checkboxCollapse.addEventListener('change', function (e) {
    if ('checked' in e.target) {
        cal.setOptions({
            week: { collapseDuplicateEvents: !!e.target.checked },
            useDetailPopup: !e.target.checked,
        });
    }
});

// Event listener for sidebar clicks.
// This is primarily used for handling the visibility of calendar elements (e.g., showing or hiding specific calendars).
sidebar.addEventListener('click', function (e) {
    if ('value' in e.target) {
        // Handles the 'select all' or individual calendar selection functionality.
        if (e.target.value === 'all') {
            // Toggles the visibility of all calendars based on the current state.
            if (appState.activeCalendarIds.length > 0) {
                cal.setCalendarVisibility(appState.activeCalendarIds, false); // Hides all calendars.
                appState.activeCalendarIds = []; // Clears the list of active calendar IDs.
                setAllCheckboxes(false); // Unchecks all checkboxes.
            } else {
                // If no calendars are currently active, activates and shows all.
                appState.activeCalendarIds = eventTypes.map(function (eventType) {
                    return eventType.id;
                });
                cal.setCalendarVisibility(appState.activeCalendarIds, true); // Shows all calendars.
                setAllCheckboxes(true); // Checks all checkboxes.
            }
        } else {
            // Toggles the visibility of an individual calendar based on its checkbox state.
            if (appState.activeCalendarIds.indexOf(e.target.value) > -1) {
                // If the calendar is currently active, hides it and updates its state.
                appState.activeCalendarIds.splice(appState.activeCalendarIds.indexOf(e.target.value), 1);
                cal.setCalendarVisibility(e.target.value, false);
                setCheckboxBackgroundColor(e.target); // Updates the checkbox background color.
            } else {
                // If the calendar is not active, shows it and updates its state.
                appState.activeCalendarIds.push(e.target.value);
                cal.setCalendarVisibility(e.target.value, true);
                setCheckboxBackgroundColor(e.target); // Updates the checkbox background color.
            }
        }
    }
});
}

</script>


<article class="content">
  <aside class="sidebar">
    <div class="sidebar-item">
      <input class="checkbox-all" type="checkbox" id="all" value="all" checked />
      <label class="checkbox checkbox-all" for="all">View all</label>
    </div>
    <hr />
    <?php
    foreach ($eventTypes as $eventType) {
        echo '<div class="sidebar-item">';
        echo '<input type="checkbox" id="' . $eventType['id'] . '" value="' . $eventType['id'] . '" checked />';
        echo '<label class="checkbox checkbox-calendar checkbox-' . $eventType['id'] . '" for="' . $eventType['id'] . '">' . $eventType['name'] . '</label>';
        echo '</div>';
    }
    ?>


    <hr />
    <span class="app-footer">
      2024
      <a href="https://www.cesa5.org/" target="_blank" style="color: black;">
        Cooperative Educational Service Agency 5
      </a>
      . All Rights Reserved
    </span>
  </aside>
  <section class="app-column">
    <nav class="navbar">
      <div class="dropdown">
        <div class="dropdown-trigger">
          <button class="button is-rounded" aria-haspopup="true" aria-controls="dropdown-menu">
            <span class="button-text"></span>
            <span class="dropdown-icon toastui-calendar-icon toastui-calendar-ic-dropdown-arrow"></span>
          </button>
        </div>
        <div class="dropdown-menu">
          <div class="dropdown-content">
            <a href="#" class="dropdown-item" data-view-name="month">Monthly</a>
            <a href="#" class="dropdown-item" data-view-name="week">Weekly</a>
            <a href="#" class="dropdown-item" data-view-name="day">Daily</a>
          </div>
        </div>
      </div>
      <button class="button is-rounded today">Today</button>
      <button class="button is-rounded prev">
        <i class="fa fa-arrow-left" aria-hidden="true"></i>
      </button>
      <button class="button is-rounded next">
        <i class="fa fa-arrow-right" aria-hidden="true"></i>
      </button>
      <span class="navbar--range"></span>
      <div class="nav-checkbox">
        <input class="checkbox-collapse" type="checkbox" id="collapse" value="collapse" />
        <label for="collapse">Collapse duplicate events and disable the detail popup</label>
      </div>
    </nav>
    <main id="app" style="height: 72vh;"></main>
  </section>
</article>

<script src="./scripts/mock-data.js"></script>
<script src="./scripts/utils.js"></script>
<script src="./scripts/app.js"></script>