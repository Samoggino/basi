<?php
session_start();
require_once '../../helper/connessione_mysql.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Query per recuperare gli attributi di tutte le tabelle
$db = connectToDatabaseMYSQL();
$query = "SHOW TABLES";
$stmt = $db->query($query);

$tabelle = array();
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tabelle[] = $row[0];
}

$attributi = array();
foreach ($tabelle as $tabella) {
    $query = "SHOW COLUMNS FROM $tabella";
    $stmt = $db->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $attributi[$tabella][] = $row['Field'];
    }
}

// Includi l'array di attributi come parte del codice JavaScript
echo "<script>var attributiPerTabella = " . json_encode($attributi) . ";</script>";
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Dati Tabella</title>
</head>

<body>
    <h2>Inserimento Dati Tabella</h2>
    <form id="crea_tabella_form" action="../handler/factory_tabella.php" method="POST">
        <label for="nome_tabella">Nome Tabella:</label>
        <input type="text" id="nome_tabella" name="nome_tabella" required><br><br>

        <label for="numero_attributi">Numero di Attributi:</label>
        <input type="number" id="numero_attributi" name="numero_attributi" min="1" required><br><br>

        <div id="attributi_container"></div><br><br>

        <input type="submit" value="Crea">
    </form>


    <script>
        document.getElementById("numero_attributi").addEventListener("change", function() {
            var numeroAttributi = parseInt(this.value);
            var container = document.getElementById("attributi_container");
            container.innerHTML = '';

            for (var i = 0; i < numeroAttributi; i++) {
                var div = document.createElement("div");
                div.innerHTML = '<label for="nome_attributo_' + i + '">Nome Attributo ' + (i + 1) + ':</label>' +
                    '<input type="text" id="nome_attributo_' + i + '" name="nome_attributo[]" required>' +
                    '<label for="tipo_attributo_' + i + '">Tipo Attributo ' + (i + 1) + ':</label>' +
                    '<select id="tipo_attributo_' + i + '" name="tipo_attributo[]" required>' +
                    '<option value="INT">INT</option>' +
                    '<option value="VARCHAR">VARCHAR</option>' +
                    '<option value="DATE">DATE</option>' +
                    '</select>' +
                    '<input type="checkbox" id="primary_key_' + i + '" name="primary_key[]" value="' + i + '">' +
                    '<label for="primary_key_' + i + '">Primary Key</label>' +
                    '<input type="checkbox" id="foreign_key_' + i + '" name="foreign_key[]" onchange="foreingKeyChecked(' + i + ')" value="' + i + '">' +
                    '<label for="foreign_key_' + i + '">Foreign Key</label>' +
                    '<div id="foreign_key_options_' + i + '" style="display: none;">' +
                    '<label for="tabella_vincolata_' + i + '">Tabella Vincolata:</label>' +
                    '<select id="tabella_vincolata_' + i + '" name="tabella_vincolata[]" onchange="populateAttributi(' + i + ')">' +
                    '</select><br><br>' +
                    '<label for="attributo_vincolato_' + i + '">Attributo Vincolato:</label>' +
                    '<select id="attributo_vincolato_' + i + '" name="attributo_vincolato[]"></select><br><br>' +
                    '</div>' +
                    '<br><br>';
                container.appendChild(div);

                // Popola le opzioni per la tabella vincolata
                var tabellaVincolataSelect = document.getElementById("tabella_vincolata_" + i);
                <?php
                $db = connectToDatabaseMYSQL();
                $query = "SELECT nome_tabella FROM TABELLA_DELLE_TABELLE";
                $stmt = $db->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    echo 'var option = document.createElement("option");';
                    echo 'option.value = "' . $row[0] . '";';
                    echo 'option.textContent = "' . $row[0] . '";';
                    echo 'tabellaVincolataSelect.appendChild(option);';
                }
                ?>
            }
        });

        // Mostra gli attributi corrispondenti alla tabella selezionata per la foreign key
        function populateAttributi(index) {
            var tabellaVincolata = document.getElementById("tabella_vincolata_" + index).value;
            var attributoVincolatoSelect = document.getElementById("attributo_vincolato_" + index);
            attributoVincolatoSelect.innerHTML = '';

            var attributi = attributiPerTabella[tabellaVincolata];
            for (var j = 0; j < attributi.length; j++) {
                var option = document.createElement("option");
                option.value = attributi[j];
                option.textContent = attributi[j];
                attributoVincolatoSelect.appendChild(option);
            }
        }

        // Aggiungi un gestore di eventi per il submit del modulo
        document.getElementById("crea_tabella_form").addEventListener("submit", function(event) {
            // Recupera tutte le checkbox delle chiavi primarie
            var checkboxes = document.getElementsByName("primary_key[]");
            var isChecked = false;

            // Verifica se almeno una checkbox è stata selezionata
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    isChecked = true;
                    break;
                }
            }

            // Se nessuna checkbox è ,selezionata impedisci l'invio del modulo
            if (!isChecked) {
                event.preventDefault();
                alert("È necessario selezionare almeno una chiave primaria.");
            }
        });

        function foreingKeyChecked(index) {
            var foreignKeyCheckbox = document.getElementById("foreign_key_" + index);
            var optionsDiv = document.getElementById("foreign_key_options_" + index);
            if (foreignKeyCheckbox.checked) {
                optionsDiv.style.display = "block";
            } else {
                optionsDiv.style.display = "none";
            }
        }

        // Mostra le opzioni per la foreign key quando la checkbox è selezionata
        var foreignKeyCheckboxes = document.getElementsByName("foreign_key[]");
        console.log(foreignKeyCheckboxes);
        for (var i = 0; i < foreignKeyCheckboxes.length; i++) {
            foreignKeyCheckboxes[i].addEventListener("change", function() {
                var index = parseInt(this.id.split("_")[1]); // Ottieni l'indice dall'ID dell'elemento
                var optionsDiv = document.getElementById("foreign_key_options_" + index);
                if (this.checked) {
                    optionsDiv.style.display = "block";
                } else {
                    optionsDiv.style.display = "none";
                }
            });
        }
    </script>



</body>

</html>