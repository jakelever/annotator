$(document).ready(function() {
  $('.example-genes .typeahead').typeahead({
    name: 'genes',
    remote: 'geneSearch.php?q=%QUERY',
    limit: 10
    });
    
  $('.example-drugs .typeahead').typeahead({
    name: 'drugs',
    remote: 'drugSearch.php?q=%QUERY',
    limit: 10
    });
});


function setSNV(name)
{
    $('#button_variant').button('toggle');
    document.getElementById("snvTextbox").value = name;
}

function setDrug(name)
{
    document.getElementById("drugTextbox").value = name;
}

function setGene(name)
{
    document.getElementById("geneTextbox").value = name;
}

function setFusionGene(name)
{
    document.getElementById("fusiongeneTextbox").value = name;
}