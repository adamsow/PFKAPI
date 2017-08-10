<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';

function GetExhibitions($db, $log, $filter){
	$log -> addInfo("Getting exhibitions.");
	switch($filter)
	{
		case "all":
			$condition = 'ORDER by data desc';
			break;
		case "active":
			$condition = 'where data >= CURDATE()';
			break;
		case "not-active":
			$condition = 'where data < CURDATE() ORDER by data desc';
			break;
		default:
			$condition = '';
			break;
	}
	
	$stmt = $db->prepare("SELECT id_wystawa as id, pelna_nazwa as name, data as date, data >= CURDATE() as isActive FROM wystawa " . $condition . ";");

	$stmt->execute();
	$exhibitions = json_encode($stmt->fetchAll());
	
	return $exhibitions;
}

function GetExhibitionById($db, $log, $id, $dbw)
{
	$log -> addInfo("Getting exhibition by ID.");	
	$stmt = $db->prepare("SELECT w.rodzaj as category, w.oddzial as department, w.miejscowosc as city, w.creator, w.changed_by, w.created, w.changed as modified, w.zgloszenia_otwarte as status
		FROM wystawa w
		WHERE id_wystawa = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$exhibition = $stmt->fetchAll();
	$createdBy = GetUser($exhibition[0]['creator'], $dbw);
	$modifiedBy = GetUser($exhibition[0]['changed_by'], $dbw);
	$exhibition['createdBy'] = $createdBy;
	$exhibition['modifiedBy'] = $modifiedBy;
	
	$exhibition = json_encode($exhibition);
	
	return $exhibition;
}

function AddExhibition($data, $db, $log, $userId)
{
	if(!CheckNewExhibitionData($data))
		return false;
	
	$log -> addInfo("Adding new exhibition: " . $data->name);
	$shortName = "wystawa-" . $data->date;
	$stmt = $db->prepare("INSERT INTO wystawa (pelna_nazwa, nazwa, rodzaj, miejscowosc, oddzial, data, creator, created, changed, changed_by, zgloszenia_otwarte)
							VALUES (:fullExName, :name, :rang, :city, :department, :date, :createdBy, NOW(), NOW(), :createdBy, :status);");
	$stmt->bindParam(':fullExName', $data->name);
	$stmt->bindParam(':name', $shortName);
	$stmt->bindParam(':rang', $data->rang);
	$stmt->bindParam(':city', $data->city);
	$stmt->bindParam(':department', $data->department);
	$stmt->bindParam(':date', $data->date);
	$stmt->bindParam(':createdBy', $userId);
	$stmt->bindParam(':status', $data->status);
	$stmt->execute();

	return true;
}

function UpdateExhibition($data, $db, $log, $userId)
{
	if(!CheckUpdateExhibitionData($data))
		return false;
	
	$log -> addInfo("Updating exhibition: " . $data->name);
	$shortName = "wystawa" . $data->date;
	$stmt = $db->prepare("Update wystawa set pelna_nazwa = :fullExName, nazwa = :name, rodzaj = :rang, miejscowosc = :city, oddzial = :department, data = :date,
						changed = NOW(), changed_by = :createdBy, zgloszenia_otwarte = :status where id_wystawa = :id");
	$stmt->bindParam(':fullExName', $data->name);
	$stmt->bindParam(':name', $shortName);
	$stmt->bindParam(':rang', $data->rang);
	$stmt->bindParam(':city', $data->city);
	$stmt->bindParam(':department', $data->department);
	$stmt->bindParam(':date', $data->date);
	$stmt->bindParam(':createdBy', $userId);
	$stmt->bindParam(':status', $data->status);
	$stmt->bindParam(':id', $data->id);
	$stmt->execute();

	return true;
}

function RemoveExhibition($id, $db, $log, $userId)
{
	$log -> addInfo("Removing exhibition: " . $id . " by " . $userId);
	$stmt = $db->prepare("Delete from Uczestnicy where wystawa_id = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$stmt = $db->prepare("Delete from wystawa where id_wystawa = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}