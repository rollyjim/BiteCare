const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// USERS CHART
new Chart(document.getElementById('usersChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Users',
            data: usersData,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    }
});

// REPORTS CHART
new Chart(document.getElementById('reportsChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Bite Reports',
            data: reportsData,
            backgroundColor: 'rgba(255, 99, 132, 0.6)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    }
});

// PENDING CHART
new Chart(document.getElementById('pendingChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Pending Reports',
            data: pendingData,
            backgroundColor: 'rgba(255, 206, 86, 0.6)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1
        }]
    }
});

// HIGH RISK CHART
new Chart(document.getElementById('highRiskChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'High Risk Cases',
            data: highRiskData,
            borderColor: 'rgba(255, 0, 0, 1)',
            backgroundColor: 'rgba(255, 0, 0, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    }
});
