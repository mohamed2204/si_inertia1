SELECT
  promotions.nom AS p,
  specialites.nom AS s,
  phases.nom AS ph,
  matieres.nom AS m,
  programme_matieres.coefficient,
  programme_matieres.matiere_id 
FROM
  promotions
  INNER JOIN promotion_specialites ON promotions.id = promotion_specialites.promotion_id
  INNER JOIN specialites ON specialites.id = promotion_specialites.specialite_id
  INNER JOIN promotion_specialite_phases ON promotions.id = promotion_specialite_phases.promotion_id 
  AND specialites.id = promotion_specialite_phases.specialite_id
  INNER JOIN programme_matieres ON specialites.id = programme_matieres.specialite_id
  INNER JOIN phases ON programme_matieres.phase_id = phases.id 
  AND promotion_specialite_phases.phase_id = phases.id
  INNER JOIN matieres ON programme_matieres.matiere_id = matieres.id 
ORDER BY
  phases.ordre ASC