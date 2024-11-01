var jq=jQuery.noConflict();
jq( document ).ready(function() {

  google.charts.load("current", {packages:["corechart"]});
  google.charts.setOnLoadCallback(drawChart);
  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['Pac Man', 'Percentage'],
      ['', 75],
      ['', 25]
    ]);

    var data1 = google.visualization.arrayToDataTable([
      ['Pac', 'Percentage'],
      ['', 50],
      ['', 50]
    ]);

    var data2 = google.visualization.arrayToDataTable([
      ['Pac', 'Percentage'],
      ['', 25],
      ['', 75]
    ]);

    var data3 = google.visualization.arrayToDataTable([
      ['Pac', 'Percentage'],
      ['', 0],
      ['', 100]
    ]);

    var options = {
      legend: 'none',
      pieSliceText: 'none',
      pieStartAngle: 180,
      pieSliceBorderColor: {
        color: 'black',
      },
      backgroundColor: '#fafafa',
      pieHole: 0,
      tooltip: { trigger: 'none' },
      slices: {
        0: { color: 'white' },
        1: { color: 'green' }
      }
    };

    var chart = new google.visualization.PieChart(document.getElementById('installment1'));
    var chart1 = new google.visualization.PieChart(document.getElementById('installment2'));
    var chart2 = new google.visualization.PieChart(document.getElementById('installment3'));
    var chart3 = new google.visualization.PieChart(document.getElementById('installment4'));
    
    jQuery( "input:radio[name=payment_method]" ).on( "change", function() {
      if(jq(this).val() == 'wizardpay'){
      
      console.log('change');
      chart.draw(data, options);
      chart1.draw(data1, options);
      chart2.draw(data2, options);
      chart3.draw(data3, options);
    }
  });

    chart.draw(data, options);
    chart1.draw(data1, options);
    chart2.draw(data2, options);
    chart3.draw(data3, options);
  }
});