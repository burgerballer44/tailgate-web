// global
let httpRequest;

// elements in the DOM
const seasonDiv = document.querySelector(".season_div");
const teamDiv = document.querySelector(".team_div");
const sportSelect = document.querySelector("select[name='sport']");
const seasonSelect = document.querySelector("select[name='season_id']");
const teamSelect = document.querySelector("select[name='team_id']");

// hide inputs on page load
seasonDiv.classList.add('hidden');
teamDiv.classList.add('hidden');

// when the sport dropdown is changed
sportSelect.addEventListener('change', function() {
    
    // hide the team div
    teamDiv.classList.add('hidden');

    // clear season and team dropdown
    seasonSelect.options.length = 0;
    teamSelect.options.length = 0;

    // add default options to dropdown
    let option = document.createElement('option');
    option.selected = 'selected';
    option.value = '';
    option.disabled = true;
    option.innerHTML = 'What season...';
    seasonSelect.add(option);
    option = document.createElement('option');
    option.selected = 'selected';
    option.value = '';
    option.disabled = true;
    option.innerHTML = 'What team...';
    teamSelect.add(option);

    // get selected sport
    let sportValue = this.value;

    // turn object into array
    let seasonsBySport = Object.entries(seasons[sportValue]);

    // add season options to dropdown
    seasonsBySport.forEach(function(season) {
        option = document.createElement('option');
        option.value = season[0];
        option.innerHTML = season[1];
        seasonSelect.add(option);
    });

    // show the season div
    seasonDiv.classList.remove('hidden');
});

// when the season dropdown is changed
seasonSelect.addEventListener('change', getTeams)

function getTeams() {

    // clear season and team dropdown
    teamSelect.options.length = 0;

    // add default options to team dropdown
    let option = document.createElement('option');
    option.selected = 'selected';
    option.value = '';
    option.disabled = true;
    option.innerHTML = 'What team...';
    teamSelect.add(option);

    httpRequest = new XMLHttpRequest();

    let seasonId = seasonSelect.value;

    if (!httpRequest) {
        alert('Giving up :( Cannot create an XMLHTTP instance');
        return false;
    }
    httpRequest.onreadystatechange = updateTeams;
    httpRequest.open('GET', `/season/teamlist/${seasonId}`);
    httpRequest.send();
}

function updateTeams() {
    if (httpRequest.readyState === XMLHttpRequest.DONE) {
        if (httpRequest.status === 200) {
            let teams = JSON.parse(httpRequest.responseText);

            // add team options to dropdown
            teams.forEach(function(team) {
                option = document.createElement('option');
                option.value = team.teamId;
                option.innerHTML = team.teamName;
                teamSelect.add(option);
            });

            // show the team div
            teamDiv.classList.remove('hidden');

        } else {
            alert('There was a problem with the request.');
        }
    }
}