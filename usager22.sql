-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : ven. 15 mai 2026 à 12:51
-- Version du serveur : 10.11.14-MariaDB-0ubuntu0.24.04.1
-- Version de PHP : 8.3.6

DROP DATABASE dbdarquest14;
CREATE DATABASE dbdarquest14;
USE dbdarquest14;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `usager22`
--

delimiter //

CREATE FUNCTION GetItemTypeName(Internal VARCHAR(1))
RETURNS VARCHAR(8) DETERMINISTIC
BEGIN
  return (
    CASE Internal
      WHEN 'A' THEN 'Arme'
      WHEN 'R' THEN 'Armure'
      WHEN 'P' THEN 'Potion'
      WHEN 'S' THEN 'Sort'
    END
    );
END;
//

CREATE PROCEDURE GetMarketItems(IN p_limit INT, IN search VARCHAR(100), IN sort CHAR(1))
BEGIN
    DECLARE fuzz VARCHAR(100);
    SET fuzz = CONCAT('%', search, '%');

    SET @sql = CONCAT(
      'SELECT i.IdItem, i.Nom, GetItemTypeName(i.Type) as NomType, SUM(m.Quantite) AS Quantite, i.Prix, i.Description, i.image, j.Alias AS Vendeur ',
      'FROM Items i ',
      'INNER JOIN Marche m ON m.IdItem = i.IdItem ',
      'INNER JOIN Joueurs j ON m.IdJoueur = j.IdJoueur ',
      'WHERE i.Nom LIKE ? OR i.Description LIKE ? OR GetItemTypeName(i.Type) LIKE ? ',
      'GROUP BY i.IdItem, i.Nom, i.Type, i.Prix, i.Description, i.image ',
      'ORDER BY ',
      CASE sort
        WHEN 'A' THEN 'i.Prix '
        WHEN 'D' THEN 'i.Prix DESC '
        ELSE 'i.Nom '
      END,
      'LIMIT ? '
    );

    PREPARE stmt FROM @sql;
    EXECUTE stmt USING fuzz, fuzz, fuzz, p_limit;
    DEALLOCATE PREPARE stmt;
END
//

CREATE PROCEDURE GetItemById(Id INT)
BEGIN 
    SELECT i.IdItem, i.Nom, SUM(m.Quantite) AS Quantite, i.Prix, i.Description, i.image, GetItemTypeName(i.Type) as Type, j.Alias AS Vendeur
    FROM Items i
    INNER JOIN Marche m ON m.IdItem = i.IdItem
    INNER JOIN Joueurs j ON m.IdJoueur = j.IdJoueur
    WHERE i.IdItem = Id
    GROUP BY i.IdItem, i.Nom, i.Type, i.Prix, i.Description, i.image;
END;
//

CREATE FUNCTION IsItemIsOnMarket(v_IdItem int) 
RETURNS TINYINT 
DETERMINISTIC
BEGIN 
  Declare returnval TINYINT(1) DEFAULT 0;
  SELECT EXISTS (
    select 1 from 
    Marche as ma 
    where ma.IdItem = v_IdItem
  ) INTO returnval;
  return returnval;
END
//

CREATE PROCEDURE EnigmaUserStats(IdJoueur INT)
BEGIN
  SELECT
  IFNULL(SUM(Difficulte = 1), 0) AS FacileTotal,
  IFNULL(SUM(Difficulte = 1 AND Reussi = 1), 0) AS FacileSuccess,
  IFNULL(SUM(Difficulte = 2), 0) AS MoyenTotal,
  IFNULL(SUM(Difficulte = 2 AND Reussi = 1), 0) AS MoyenSuccess,
  IFNULL(SUM(Difficulte = 3), 0) AS DifficileTotal,
  IFNULL(SUM(Difficulte = 3 AND Reussi = 1), 0) AS DifficileSuccess,
  EstMage,
  StreakMagie,
  j.MagieReussies
  FROM EssaieEnigmes es
  INNER JOIN Joueurs j ON es.IdJoueur = j.IdJoueur
  INNER JOIN Enigme en ON es.IdEnigme = en.IdEnigme
  INNER JOIN CategorieEnigme c ON en.IdCategorie = c.IdCategorie
  WHERE es.IdJoueur = IdJoueur;
END
//

CREATE PROCEDURE AddItemToInventory(Joueur INT, Item INT, Qty INT)
BEGIN
  INSERT INTO Inventaires (IdJoueur, IdItem, Quantite)
    VALUES (Joueur, Item, Qty)
    ON DUPLICATE KEY UPDATE Quantite = Quantite + Qty;
END;
//

Create PROCEDURE ConvertCoinsToGold(
  IdJoueur INT
)
BEGIN
DECLARE PlayerBronze INT;
DECLARE PlayerSilver INT;
DECLARE PlayerGold INT;
Declare ConvertedBronze INT;
Declare ConvertedSilver INT;

SELECT PieceBronze,PieceArgent,PieceOr INTO PlayerBronze,PlayerSilver,PlayerGold
FROM Joueurs J
WHERE J.IdJoueur = IdJoueur; 

SELECT PlayerSilver DIV 10,PlayerBronze DIV 100 INTO ConvertedSilver,ConvertedBronze;

Update Joueurs as J
set 
PieceBronze = PlayerBronze - ConvertedBronze * 100,
PieceArgent = PlayerSilver - ConvertedSilver * 10,
PieceOr = PlayerGold + ConvertedBronze + ConvertedSilver
WHERE J.IdJoueur = IdJoueur;
END
//

CREATE PROCEDURE BuyItem(
  IdJoueur INT,
  Iditem INT,
  quantite INT
  )
BEGIN
DECLARE ItemPrice INT;
DECLARE PlayerMoney INT;
Declare TotalPrice INT;
DECLARE itemQuantity INT;
DECLARE SellerId INT; 
DECLARE InvAmount INT;
DECLARE MageCheck INT; 
DECLARE PlayerBronze INT;
DECLARE PlayerSilver INT;

Declare Itemtype VARCHAR(20);

SELECT Prix, Type INTO ItemPrice, Itemtype
 FROM Items
 WHERE Iditem = Items.IdItem;
SELECT EstMage INTO MageCheck FROM Joueurs j WHERE j.IdJoueur = IdJoueur;
SELECT Quantite, m.IdJoueur INTO itemQuantity, SellerId
FROM Marche m 
WHERE m.IdItem = Iditem 
LIMIT 1;
SELECT PieceOr INTO PlayerMoney
FROM Joueurs
WHERE IdJoueur = Joueurs.IdJoueur;

CALL ConvertCoinsToGold(IdJoueur);
IF MageCheck = 0 && Itemtype = "S" THEN 
   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = "cannot buy spells if you aren't a wizard";
END IF; 
 IF Itemprice IS NOT NULL AND itemQuantity>=quantite THEN 
    SET TotalPrice = ItemPrice * Quantite;
    IF PlayerMoney >= TotalPrice THEN 
      
     UPDATE Joueurs SET  Joueurs.PieceOr  = Joueurs.PieceOr - TotalPrice
     WHERE IdJoueur = Joueurs.IdJoueur;
     UPDATE Joueurs 
     SET Joueurs.PieceOr = Joueurs.PieceOr + TotalPrice * IF(ItemType = 'S', 1.1, 0.6)
     WHERE Joueurs.IdJoueur = SellerId;

      UPDATE Marche SET Marche.Quantite = Marche.Quantite - quantite
      WHERE Iditem = Marche.IdItem;
      SELECT count(*) INTO InvAmount 
      from Inventaires Inv
      where Inv.IdJoueur = IdJoueur AND Inv.IdItem = Iditem;
      Delete from Marche
      where Marche.Quantite = 0;
      if InvAmount = 0 THEN 
        insert 
        into Inventaires (IdJoueur,IdItem,Quantite)
        VALUES (IdJoueur, Iditem, quantite);
      ELSE 
        UPDATE  Inventaires 
        SET Quantite = Quantite + quantite
        WHERE Inventaires.IdJoueur = IdJoueur AND Inventaires.IdItem = Iditem;
      END IF; 
    ELSE 
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient funds';
    END IF; 
 ELSE 
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Item not found';
END IF;
END
//

CREATE PROCEDURE SellItem
(
IdJoueur INT,
IdItem INT,
Quantite INT
)
BEGIN
  DECLARE InventoryQt INT;
  Declare MarketItemCount INT;
  
 SELECT Quantite INTO InventoryQt
  FROM Inventaires as inv
  WHERE inv.IdJoueur = IdJoueur AND inv.IdItem = IdItem;

  IF InventoryQt >= Quantite THEN
    UPDATE Inventaires as inv
    SET inv.Quantite = inv.Quantite- Quantite
    WHERE inv.IdJoueur = IdJoueur AND inv.IdItem = IdItem;
    SELECT COUNT(*) INTO MarketItemCount
    FROM Marche WHERE Marche.IdItem = IdItem AND Marche.IdJoueur = IdJoueur;
    DELETE FROM Inventaires WHERE Inventaires.Quantite = 0;
    if MarketItemCount = 0 THEN
      INSERT INTO Marche (IdJoueur,IdItem,Quantite)
       VALUES (IdJoueur,IdItem,Quantite);
    ELSE
      UPDATE Marche m
      set m.Quantite = m.Quantite + Quantite
      WHERE m.IdItem = IdItem AND IdJoueur = m.IdJoueur;
    END IF;
    
  ELSE
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Not enough items in inventory';
  END IF;
END;
//

CREATE PROCEDURE RemoveItemFromMarket(Joueur INT, Item INT, Qty INT)
BEGIN
  DECLARE available INT;
  SELECT Quantite INTO available FROM Marche WHERE IdJoueur = Joueur AND IdItem = Item;

  IF available >= Qty THEN
    UPDATE Marche m SET m.Quantite = m.Quantite - Qty WHERE IdJoueur = Joueur AND IdItem = Item;
    DELETE FROM Marche WHERE Quantite = 0;
    CALL AddItemToInventory(Joueur, Item, Qty);
  ELSE
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Not enough items in inventory';
  END IF;
END;
//

CREATE PROCEDURE CreateSort(Nom VARCHAR(45), Prix INT, Description VARCHAR(300), image VARCHAR(300), Instantane TINYINT(1), Degats INT, Soins INT)
BEGIN
  INSERT INTO Items (Nom, Type, Prix, Description, image) VALUES
    (Nom, 'S', Prix, Description, image);
  INSERT INTO Sorts (IdItem, Instantane, PointDeDegat, Soins) VALUES
    (LAST_INSERT_ID(), Instantane, Degats, Soins);
END;
//

CREATE PROCEDURE CreateArme(Nom VARCHAR(45), Prix INT, Description VARCHAR(300), image VARCHAR(300), Efficacite INT, Genre VARCHAR(45))
BEGIN
  INSERT INTO Items (Nom, Type, Prix, Description, image) VALUES
    (Nom, 'A', Prix, Description, image);
  INSERT INTO Armes (IdItem, Efficacite, Genre) VALUES
    (LAST_INSERT_ID(), Efficacite, Genre);
END;
//

CREATE PROCEDURE CreateArmure(Nom VARCHAR(45), Prix INT, Description VARCHAR(300), image VARCHAR(300), Taille VARCHAR(45), Matiere VARCHAR(45))
BEGIN
  INSERT INTO Items (Nom, Type, Prix, Description, image) VALUES
    (Nom, 'R', Prix, Description, image);
  INSERT INTO Armures (IdItem, Taille, Matiere) VALUES
    (LAST_INSERT_ID(), Taille, Matiere);
END;
//

CREATE PROCEDURE CreatePotion(Nom VARCHAR(45), Prix INT, Description VARCHAR(300), image VARCHAR(300), Effet VARCHAR(45), Duree INT, Soins INT)
BEGIN
  INSERT INTO Items (Nom, Type, Prix, Description, image) VALUES
    (Nom, 'P', Prix, Description, image);
  INSERT INTO Potions (IdItem, Effet, Duree, Soins) VALUES
    (LAST_INSERT_ID(), Effet, Duree, Soins);
END;
//

delimiter ;


-- --------------------------------------------------------

--
-- Structure de la table `Achats`
--

CREATE TABLE `Achats` (
  `IdJoueur` int(11) NOT NULL,
  `IdItem` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Armes`
--

CREATE TABLE `Armes` (
  `IdItem` int(11) NOT NULL,
  `Efficacite` int(11) NOT NULL,
  `Genre` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Armes`
--

INSERT INTO `Armes` (`IdItem`, `Efficacite`, `Genre`) VALUES
(1, 1, 'Deux mains');

-- --------------------------------------------------------

--
-- Structure de la table `Armures`
--

CREATE TABLE `Armures` (
  `IdItem` int(11) NOT NULL,
  `Taille` varchar(45) NOT NULL,
  `Matiere` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Armures`
--

INSERT INTO `Armures` (`IdItem`, `Taille`, `Matiere`) VALUES
(2, 'Grande', 'Fer');

-- --------------------------------------------------------

--
-- Structure de la table `CategorieEnigme`
--

CREATE TABLE `CategorieEnigme` (
  `IdCategorie` int(11) NOT NULL,
  `Categorie` varchar(45) NOT NULL,
  `EstMagie` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `CategorieEnigme`
--

INSERT INTO `CategorieEnigme` (`IdCategorie`, `Categorie`, `EstMagie`) VALUES
(1, 'Culture Générale', 0),
(2, 'Magie', 1);

-- --------------------------------------------------------

--
-- Structure de la table `Enigme`
--

CREATE TABLE `Enigme` (
  `IdEnigme` int(11) NOT NULL,
  `IdCategorie` int(11) NOT NULL,
  `Difficulte` int(11) NOT NULL,
  `Question` varchar(100) NOT NULL,
  `Reponse1` varchar(255) NOT NULL,
  `Reponse2` varchar(255) NOT NULL,
  `Reponse3` varchar(255) NOT NULL,
  `Reponse4` varchar(255) NOT NULL,
  `BonneReponse` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Enigme`
--

INSERT INTO `Enigme` (`IdEnigme`, `IdCategorie`, `Difficulte`, `Question`, `Reponse1`, `Reponse2`, `Reponse3`, `Reponse4`, `BonneReponse`) VALUES
(1, 1, 1, 'Quelle est la capitale de la France ?', 'Lyon', 'Paris', 'Marseille', 'Bordeaux', 2),
(2, 1, 2, 'Moyen', 'Lyon', 'Paris', 'Marseille', 'Bordeaux', 2),
(3, 2, 3, 'Enigme magie', '1', '2', '3', '4', 2),
(4, 1, 1, 'Je suis une épée', 'Dague', 'Épée', 'Marteau', 'Arc', 2),
(5, 2, 2, 'L\'armure la plus forte est la quelle parmi les 4 choix?', 'Armure en diamant', 'Armure en or', 'Armure en cuir', 'Armure en argent', 1),
(6, 2, 2, 'Quel est le plus grand océan de la planète ?', 'Océan Indien', 'Océan Pacifique', 'Océan Atlantique', 'Océan Artique', 2),
(8, 2, 2, 'Quel est le plus grand océan de la planète ?', 'Océan Indien', 'Océan Pacifique', 'Océan Atlantique', 'Océan Artique', 2),
(11, 2, 2, 'Que fais le plus mal', 'Sorcier', 'Mage', 'Marteau', 'Squelette', 2),
(12, 2, 2, 'Quelle planète est connue comme la Planète rouge ?', 'Mars ', 'Vénus', 'Jupiter', 'Saturne', 1),
(13, 1, 1, 'test', 'Sorcier', 'Épée', 'Marteau', 'Squelette', 1),
(14, 1, 1, 'Quelle est la capitale de la France ?', 'Lyon', 'Paris', 'Marseille', 'Bordeaux', 2),
(15, 1, 2, 'Moyen', 'Lyon', 'Paris', 'Marseille', 'Bordeaux', 2),
(16, 2, 3, 'Enigme magie', '1', '2', '3', '4', 2),
(17, 2, 3, 'Quel est la masse du soleil', '1.9885 × 10³⁰ kilograms', '10cm', '100kg', '1000kg', 1),
(18, 2, 3, 'Quel est le sort de base du mage', 'Boule de feu', 'Boule de glace', 'Boule de foudre', 'Boule de terre', 1),
(19, 1, 1, 'Quelle est la couleur du ciel par temps clair ?', 'Bleu', 'Vert', 'Rouge', 'Jaune', 1),
(20, 1, 1, 'Combien y a-t-il de jours dans une semaine ?', '5', '6', '7', '8', 3),
(21, 1, 1, 'Quel animal miaule ?', 'Chien', 'Chat', 'Oiseau', 'Poisson', 2),
(22, 1, 1, 'Quelle est la première lettre de l’alphabet ?', 'A', 'B', 'C', 'D', 1),
(23, 1, 1, 'Quel fruit est jaune ?', 'Pomme', 'Banane', 'Fraise', 'Raisin', 2),
(24, 1, 2, 'Quel est le plus grand océan du monde ?', 'Atlantique', 'Indien', 'Arctique', 'Pacifique', 4),
(25, 1, 2, 'Combien font 5 x 6 ?', '11', '30', '25', '35', 2),
(26, 1, 2, 'Quel gaz respirons-nous principalement ?', 'Oxygène', 'Hydrogène', 'Azote', 'CO2', 3),
(27, 1, 2, 'Quel est le continent de l’Égypte ?', 'Asie', 'Europe', 'Afrique', 'Amérique', 3),
(28, 1, 2, 'Quel instrument a des touches noires et blanches ?', 'Guitare', 'Piano', 'Violon', 'Flûte', 2),
(29, 2, 1, 'Je suis rond et je donne de la lumière la nuit, qui suis-je ?', 'Le soleil', 'La lune', 'Une étoile', 'Une lampe', 2),
(30, 2, 1, 'Plus je sèche, plus je suis mouillé. Qui suis-je ?', 'Éponge', 'Serviette', 'Nuage', 'Pluie', 2),
(31, 2, 1, 'Je tombe sans me faire mal, qui suis-je ?', 'Pluie', 'Feuille', 'Neige', 'Vent', 3),
(32, 2, 1, 'Je peux être cassé sans être touché. Qui suis-je ?', 'Secret', 'Verre', 'Bois', 'Pierre', 1),
(33, 2, 1, 'Je monte mais ne descends jamais. Qui suis-je ?', 'Âge', 'Escalier', 'Ballon', 'Température', 1),
(34, 2, 2, 'Je parle sans bouche et j’entends sans oreilles. Qui suis-je ?', 'Téléphone', 'Écho', 'Radio', 'Vent', 2),
(35, 2, 2, 'Plus j’ai de gardiens, moins je suis en sécurité. Qui suis-je ?', 'Secret', 'Trésor', 'Maison', 'Ville', 1),
(36, 2, 2, 'Je suis toujours devant toi mais tu ne peux pas me voir. Qui suis-je ?', 'Futur', 'Air', 'Ombre', 'Mur', 1),
(37, 2, 2, 'Je peux remplir une pièce sans prendre de place. Qui suis-je ?', 'Air', 'Lumière', 'Son', 'Odeur', 2),
(38, 2, 2, 'Qu’est-ce qui a des clés mais n’ouvre pas de portes ?', 'Clavier', 'Carte', 'Serrure', 'Coffre', 1),
(39, 1, 1, 'BOBBBB', 'BOBBBB1', 'BOBBBB3', 'BOBBBB2', 'BOBBBB4', 1);

-- --------------------------------------------------------

--
-- Structure de la table `EssaieEnigmes`
--

CREATE TABLE `EssaieEnigmes` (
  `IdEssaie` int(11) NOT NULL,
  `IdJoueur` int(11) NOT NULL,
  `IdEnigme` int(11) NOT NULL,
  `Reussi` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `EssaieEnigmes`
--

INSERT INTO `EssaieEnigmes` (`IdEssaie`, `IdJoueur`, `IdEnigme`, `Reussi`) VALUES
(1, 1, 1, 1),
(2, 1, 3, 1),
(3, 1, 3, 1),
(4, 1, 4, 1),
(5, 13, 3, 1),
(6, 13, 3, 1),
(7, 13, 3, 1),
(8, 11, 3, 1),
(9, 11, 3, 1),
(10, 11, 3, 1),
(11, 11, 3, 1),
(12, 11, 3, 1),
(13, 11, 3, 1),
(14, 1, 3, 1),
(15, 1, 3, 1),
(16, 1, 3, 1),
(17, 1, 3, 1),
(18, 1, 3, 1),
(19, 1, 3, 1),
(20, 1, 1, 1),
(21, 1, 2, 1),
(22, 1, 3, 0),
(23, 1, 3, 0),
(24, 1, 3, 0),
(25, 1, 3, 0),
(26, 1, 3, 0),
(27, 1, 3, 0),
(28, 1, 3, 0),
(29, 1, 3, 0),
(30, 1, 3, 0),
(31, 1, 3, 0),
(32, 1, 3, 0),
(33, 1, 3, 0),
(34, 1, 3, 0),
(35, 1, 3, 1),
(36, 1, 3, 1),
(37, 1, 3, 1),
(38, 1, 4, 1),
(39, 1, 4, 0),
(40, 1, 4, 0),
(41, 1, 3, 0),
(42, 1, 3, 0),
(43, 1, 3, 0),
(44, 1, 3, 0),
(45, 1, 3, 0),
(46, 1, 3, 0),
(47, 1, 3, 0),
(48, 1, 3, 0),
(49, 1, 3, 0),
(50, 1, 3, 1),
(51, 1, 3, 0),
(52, 1, 3, 1),
(53, 9, 3, 1),
(54, 9, 3, 0),
(55, 9, 3, 1),
(56, 9, 3, 1),
(57, 9, 3, 0),
(58, 9, 3, 1),
(59, 9, 3, 1),
(60, 9, 3, 1),
(61, 9, 3, 1),
(62, 9, 3, 1),
(63, 13, 3, 0),
(64, 13, 3, 1),
(65, 13, 3, 1),
(66, 13, 3, 0),
(67, 13, 3, 1),
(68, 13, 3, 1),
(69, 13, 3, 1),
(70, 13, 3, 1),
(71, 13, 3, 1),
(72, 13, 3, 1),
(73, 13, 3, 0),
(74, 13, 3, 1),
(75, 13, 3, 0),
(76, 13, 4, 1),
(77, 13, 4, 0),
(78, 13, 4, 0),
(79, 13, 4, 1),
(80, 13, 3, 1),
(81, 13, 3, 0),
(82, 13, 3, 1),
(83, 13, 3, 1),
(84, 13, 3, 1),
(85, 13, 4, 0),
(86, 13, 3, 0),
(87, 18, 3, 1),
(88, 18, 3, 0),
(89, 18, 3, 0),
(90, 18, 3, 0),
(91, 18, 3, 0),
(92, 18, 3, 0),
(93, 18, 3, 0),
(94, 18, 3, 0),
(95, 18, 3, 0),
(96, 18, 3, 0),
(97, 18, 4, 0),
(98, 18, 2, 0),
(99, 18, 4, 0),
(100, 18, 2, 0),
(101, 18, 1, 0),
(102, 18, 4, 0),
(103, 1, 3, 1),
(104, 1, 3, 1),
(105, 1, 3, 1),
(106, 1, 3, 1),
(107, 1, 3, 1),
(108, 1, 3, 1),
(109, 19, 3, 1),
(110, 19, 3, 1),
(111, 19, 3, 1),
(112, 19, 3, 1),
(113, 19, 3, 1),
(114, 4, 3, 1),
(115, 4, 3, 1),
(116, 4, 3, 1),
(117, 4, 3, 1),
(118, 4, 3, 1),
(119, 4, 3, 1),
(120, 1, 3, 1),
(121, 1, 2, 1),
(122, 1, 4, 1),
(123, 1, 2, 1),
(124, 1, 4, 0),
(125, 20, 5, 1),
(126, 20, 1, 1),
(127, 20, 3, 1),
(128, 20, 5, 1),
(129, 20, 3, 1),
(130, 20, 2, 1),
(131, 20, 3, 1),
(132, 20, 4, 1),
(133, 20, 4, 1),
(134, 20, 2, 1),
(135, 20, 5, 1),
(136, 1, 3, 0),
(137, 21, 3, 0),
(138, 21, 3, 0),
(139, 21, 3, 0),
(140, 21, 3, 0),
(141, 21, 3, 0),
(142, 21, 3, 0),
(143, 21, 3, 0),
(144, 21, 1, 0),
(145, 21, 3, 0),
(146, 21, 3, 0),
(147, 21, 3, 0),
(148, 21, 3, 0),
(149, 21, 3, 1),
(150, 20, 1, 1),
(151, 20, 1, 1),
(152, 20, 4, 1),
(153, 20, 4, 1),
(154, 20, 5, 1),
(155, 20, 5, 1),
(156, 20, 2, 0),
(157, 20, 2, 1),
(158, 20, 1, 1),
(159, 20, 3, 1),
(160, 20, 3, 1),
(161, 21, 4, 1),
(162, 21, 4, 0),
(163, 21, 3, 0),
(164, 21, 3, 0),
(165, 21, 3, 0),
(166, 21, 3, 0),
(167, 21, 3, 0),
(168, 21, 3, 0),
(169, 21, 3, 0),
(170, 21, 3, 0),
(171, 21, 3, 0),
(172, 21, 3, 0),
(173, 21, 3, 0),
(174, 21, 3, 0),
(175, 21, 3, 0),
(176, 21, 3, 0),
(177, 21, 3, 0),
(178, 21, 3, 0),
(179, 21, 3, 0),
(180, 21, 3, 0),
(181, 21, 3, 0),
(182, 21, 3, 0),
(183, 21, 3, 0),
(184, 21, 3, 0),
(185, 21, 3, 1),
(186, 21, 3, 1),
(187, 23, 3, 0),
(188, 2, 4, 1),
(189, 2, 3, 1),
(190, 23, 3, 0),
(191, 23, 3, 0),
(192, 23, 3, 0),
(193, 23, 3, 0),
(194, 23, 3, 0),
(195, 23, 3, 0),
(196, 23, 3, 0),
(197, 23, 3, 0),
(198, 23, 3, 0),
(199, 23, 3, 1),
(200, 23, 3, 0),
(201, 23, 3, 1),
(202, 2, 2, 1),
(203, 20, 4, 1),
(204, 20, 1, 1),
(205, 20, 4, 1),
(206, 20, 1, 1),
(207, 20, 4, 1),
(208, 20, 4, 1),
(209, 20, 1, 1),
(210, 20, 5, 1),
(211, 20, 11, 1),
(212, 1, 4, 1),
(213, 1, 5, 0),
(214, 1, 13, 1),
(215, 1, 5, 1),
(216, 1, 11, 0),
(217, 1, 12, 1),
(218, 1, 12, 1),
(219, 1, 6, 1),
(220, 1, 11, 1),
(221, 1, 6, 1),
(222, 1, 3, 1),
(223, 25, 11, 1),
(224, 25, 11, 1),
(225, 25, 5, 1),
(226, 25, 4, 1),
(227, 25, 13, 1),
(228, 25, 13, 0),
(229, 25, 3, 1),
(230, 25, 2, 1),
(231, 25, 5, 1),
(232, 25, 11, 0),
(233, 25, 3, 0),
(234, 26, 12, 1),
(235, 26, 12, 0),
(236, 26, 3, 1),
(237, 26, 3, 1),
(238, 26, 3, 1),
(239, 26, 3, 0),
(240, 26, 3, 1),
(241, 26, 3, 1),
(242, 26, 2, 1),
(243, 26, 4, 1),
(244, 26, 11, 1),
(245, 27, 2, 0),
(246, 27, 11, 0),
(247, 27, 4, 1),
(248, 4, 1, 0),
(249, 4, 3, 0),
(250, 4, 6, 1),
(251, 1, 1, 0),
(252, 1, 32, 0),
(253, 1, 19, 1),
(254, 1, 3, 1),
(255, 1, 3, 0),
(256, 1, 26, 1),
(257, 1, 2, 1),
(258, 1, 35, 1),
(259, 1, 2, 0),
(260, 1, 4, 0),
(261, 1, 16, 1),
(262, 1, 33, 1),
(263, 1, 1, 0),
(264, 1, 39, 1),
(265, 1, 21, 0),
(266, 1, 19, 0),
(267, 1, 30, 0),
(268, 1, 21, 1),
(269, 1, 4, 1),
(270, 1, 14, 1),
(271, 1, 4, 1),
(272, 1, 33, 1),
(273, 1, 33, 1),
(274, 1, 39, 0),
(275, 1, 32, 1),
(276, 1, 28, 1),
(277, 1, 23, 1),
(278, 2, 13, 0),
(279, 39, 38, 1),
(280, 39, 3, 1),
(281, 39, 22, 1),
(282, 39, 20, 0);

-- --------------------------------------------------------

--
-- Structure de la table `Evaluations`
--

CREATE TABLE `Evaluations` (
  `IdJoueur` int(11) NOT NULL,
  `IdItem` int(11) NOT NULL,
  `Etoiles` int(10) UNSIGNED NOT NULL,
  `Commentaire` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Evaluations`
--

INSERT INTO `Evaluations` (`IdJoueur`, `IdItem`, `Etoiles`, `Commentaire`) VALUES
(1, 1, 5, 'C\'est un tres bon items'),
(1, 3, 2, 'Bof'),
(1, 6, 5, 'NICE!'),
(1, 8, 5, 'Incroyable épée je recommande vraiment!!!'),
(2, 3, 1, 'Une étoile, parce qu\'on ne peut pas mettre zéro. Ce produit est une insulte pure et simple à tous les joueurs d\'AVERSE. J\'ai gaspillé mon or pour une merde qui ne sert absolument à rien, c\'est de la foutaise totale. La description est un mensonge éhonté et le vendeur devrait être banni immédiatement pour escroquerie. C\'est de la poubelle, fuyez ce produit comme la peste, c\'est de l\'argent jeté par les fenêtres. C\'est tout bonnement pathétique.'),
(4, 1, 4, 'Tres fort'),
(4, 3, 3, 'FAHHHHHHHHHHHHHHHHHHHHHHHH');

-- --------------------------------------------------------

--
-- Structure de la table `HistoriqueCapital`
--

CREATE TABLE `HistoriqueCapital` (
  `IdHistorique` int(11) NOT NULL,
  `IdJoueur` int(11) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `DateCreation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `HistoriqueCapital`
--

INSERT INTO `HistoriqueCapital` (`IdHistorique`, `IdJoueur`, `Description`, `DateCreation`) VALUES
(1, 4, 'Gain niveau 1 : +10 Or', '2026-05-01 12:39:47'),
(2, 4, 'Gain niveau 2 : +10 Argent', '2026-05-01 12:40:28'),
(3, 4, 'Gain niveau 3 : +10 Bronze', '2026-05-01 12:41:09'),
(4, 2, 'Gain niveau 1 : +10 Or', '2026-05-01 12:43:00'),
(5, 2, 'Gain niveau 1 : +10 Or', '2026-05-01 12:43:04'),
(6, 2, 'Gain niveau 3 : +10 Bronze', '2026-05-01 12:43:18'),
(7, 1, 'Gain niveau 1 : +10 Or', '2026-05-01 12:44:10'),
(8, 1, 'Gain niveau 2 : +10 Argent', '2026-05-01 12:44:16'),
(9, 1, 'Gain niveau 3 : +10 Bronze', '2026-05-01 12:44:24'),
(10, 24, 'Gain niveau 1 : +10 Or', '2026-05-01 13:36:23'),
(11, 39, 'Gain niveau 1 : +10 Or', '2026-05-08 12:56:17'),
(12, 39, 'Gain niveau 1 : +10 Or', '2026-05-08 12:56:38'),
(13, 39, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:56:40'),
(14, 39, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:57:07'),
(15, 39, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:57:08'),
(16, 39, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:57:11'),
(17, 39, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:57:26'),
(18, 39, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:57:52'),
(19, 39, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:58:01'),
(20, 40, 'Gain niveau 1 : +10 Or', '2026-05-08 12:58:03'),
(21, 40, 'Gain niveau 1 : +10 Or', '2026-05-08 12:58:10'),
(22, 40, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:58:13'),
(23, 40, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:58:19'),
(24, 40, 'Gain niveau 2 : +10 Argent', '2026-05-08 12:58:21'),
(25, 41, 'Gain niveau 1 : +10 Or', '2026-05-08 13:05:54'),
(26, 41, 'Gain niveau 1 : +10 Or', '2026-05-08 13:06:17'),
(27, 41, 'Gain niveau 2 : +10 Argent', '2026-05-08 13:06:20'),
(28, 42, 'Gain niveau 1 : +10 Or', '2026-05-08 13:11:08'),
(29, 42, 'Gain niveau 1 : +10 Or', '2026-05-08 13:11:17'),
(30, 42, 'Gain niveau 2 : +10 Argent', '2026-05-08 13:11:20'),
(31, 43, 'Gain niveau 1 : +10 Or', '2026-05-08 13:12:29'),
(32, 43, 'Gain niveau 2 : +10 Argent', '2026-05-08 13:12:44'),
(33, 43, 'Gain niveau 3 : +10 Bronze', '2026-05-08 13:12:54'),
(34, 44, 'Gain niveau 1 : +10 Or', '2026-05-08 13:17:12'),
(35, 44, 'Gain niveau 2 : +10 Argent', '2026-05-08 13:17:36'),
(36, 44, 'Gain niveau 3 : +10 Bronze', '2026-05-08 13:19:45'),
(37, 9, 'Gain niveau 1 : +10 Or', '2026-05-08 13:22:25'),
(38, 9, 'Gain niveau 2 : +10 Argent', '2026-05-08 13:23:26'),
(39, 9, 'Gain niveau 3 : +10 Bronze', '2026-05-08 13:28:08'),
(40, 6, 'Gain niveau 1 : +10 Or', '2026-05-08 13:28:35'),
(41, 6, 'Gain niveau 2 : +10 Argent', '2026-05-08 13:28:43'),
(42, 6, 'Gain niveau 3 : +10 Bronze', '2026-05-08 13:29:06');

-- --------------------------------------------------------

--
-- Structure de la table `Inventaires`
--

CREATE TABLE `Inventaires` (
  `IdJoueur` int(11) NOT NULL,
  `IdItem` int(11) NOT NULL,
  `Quantite` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Inventaires`
--

INSERT INTO `Inventaires` (`IdJoueur`, `IdItem`, `Quantite`) VALUES
(1, 8, 1),
(3, 4, 3),
(11, 5, 10),
(27, 5, 1);

-- --------------------------------------------------------

--
-- Structure de la table `Items`
--

CREATE TABLE `Items` (
  `IdItem` int(11) NOT NULL,
  `Nom` varchar(45) NOT NULL,
  `Type` varchar(1) NOT NULL,
  `Prix` int(11) NOT NULL,
  `Description` varchar(300) NOT NULL,
  `image` varchar(300) DEFAULT NULL,
  `Rarete` int(11) DEFAULT 1
) ;

--
-- Déchargement des données de la table `Items`
--

INSERT INTO `Items` (`IdItem`, `Nom`, `Type`, `Prix`, `Description`, `image`, `Rarete`) VALUES
(1, 'Épée Magique', 'A', 300, 'Une épee magique', 'epee.png', 1),
(2, 'Armure En Fer', 'R', 150, 'Une grosse armure capable de vous protéger contre les attaques!', 'amure1.png', 1),
(3, 'Potion Magique de Soin', 'P', 34, 'Une potion de soin très utiles en combat!', 'soin1.png', 1),
(4, 'Potion Magique De Glace', 'P', 26, 'Gèle les ennemies de toute tailles!', 'potion2.png', 1),
(5, 'Potion Magique De Feu', 'P', 39, 'Attention sa brule!', 'potion3.png', 1),
(6, 'Sort de Soins', 'S', 100, 'aaaaaaaaaaaaaahhhhh', 'heal.webp', 1),
(7, 'Épée du roi Arthur', 'A', 167, 'Une belle épée dangereuse', 'RoiArthur.jpg', 1),
(8, 'Épée du roi Arthur', 'A', 100, 'TESTTT', 'RoiArthur.jpg', 1);

-- --------------------------------------------------------

--
-- Structure de la table `Joueurs`
--

CREATE TABLE `Joueurs` (
  `IdJoueur` int(11) NOT NULL,
  `Alias` varchar(45) NOT NULL,
  `Nom` varchar(45) NOT NULL,
  `Prenom` varchar(45) NOT NULL,
  `MDP` varbinary(128) NOT NULL,
  `PieceBronze` int(11) NOT NULL DEFAULT 100,
  `PieceArgent` int(11) NOT NULL DEFAULT 100,
  `PieceOr` int(11) NOT NULL DEFAULT 100,
  `EstAdmin` tinyint(1) NOT NULL DEFAULT 0,
  `EstMage` tinyint(1) NOT NULL DEFAULT 0,
  `NbDemandeArgent` int(11) NOT NULL DEFAULT 0,
  `PV` int(11) NOT NULL DEFAULT 100,
  `StreakMagie` int(11) NOT NULL DEFAULT 0,
  `MagieReussies` int(11) DEFAULT 0,
  `Guid` varchar(64) DEFAULT NULL,
  `GuidReset` varchar(64) DEFAULT NULL,
  `Mail` varchar(254) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Joueurs`
--

INSERT INTO `Joueurs` (`IdJoueur`, `Alias`, `Nom`, `Prenom`, `MDP`, `PieceBronze`, `PieceArgent`, `PieceOr`, `EstAdmin`, `EstMage`, `NbDemandeArgent`, `PV`, `StreakMagie`, `MagieReussies`, `Guid`, `GuidReset`, `Mail`) VALUES
(1, 'Trosfros', 'Guichard', 'Maxime', 0x243279243130246a6a667838374f3069666748465251715932685a4f65724f55754b6c6f43644c4f506f38645079317576506a34714b633277304943, 159, 10, 2243, 1, 1, 0, 50, 1, 16, NULL, NULL, ''),
(2, 'Frou_Frou', 'Perron', 'Gabriel', 0x243279243130245363374a5972736e31395071697442427078523853656e3958697149575a3874586b476143684c6c465a7761423352414930785157, 20, 0, 217, 1, 0, 0, 97, 1, 1, NULL, NULL, ''),
(3, 'Lebon', 'Lebon', 'Pascal', 0x24327924313024416d6d4968546832456f47736469437575754e72397569474f6f444f6866444c417259365a4e6b424a334d674f7a6a305a725a6453, 0, 0, 53, 0, 0, 0, 100, 0, 0, NULL, NULL, ''),
(4, 'Orisa', 'Orisa', 'Orisa', 0x243279243130244536595a6b454a735873636a566a2e4b5649322e794f6b506955386c4b756e51524c4649326a554a622e5168575062564c7371766d, 10, 10, 277, 0, 1, 0, 87, 0, 6, NULL, 'e82a9c2b487ffe7a0093eba184845746', ''),
(5, 'fdwefewf', 'bonjour', 'salut', 0x2432792431302461764f446c396e38444c4a3444776d457864546245654a6e746c543043366b47753965724a5a2f444462795857314e652e5043414b, 100, 100, 193, 0, 0, 0, 100, 0, 0, NULL, NULL, ''),
(6, 'tamere', 'tamere', 'tamere', 0x243279243130242e4d6c30486b4533617a52746c4d6a6d45572f304b4f397a4439502e366a687956436545597041385a4c732e4c47496c7546526447, 110, 110, 110, 0, 0, 0, 100, 0, 0, NULL, NULL, ''),
(7, 'ewew', 'ewew', 'ewew', 0x24327924313024497852444b6958506f536832336c616b534c7a534e6542507a68767655436b746e36523356624b6332633968685a77446a33724757, 100, 100, 100, 0, 0, 0, 100, 0, 0, NULL, NULL, ''),
(9, 'Maxime111', 'Maxime111', 'Maxime111', 0x243279243130246d6f524532535a6241526954655a786f4a5164454e6573645a786476444c6a6164542e5037437a504361504f4e755053522e426c71, 110, 110, 290, 0, 1, 0, 80, 5, 0, NULL, NULL, ''),
(11, 'Maxime666', 'Maxime666', 'Maxime666', 0x24327924313024353977665a34566238585734795a357343666441664f2e704977427670364732535932577349727943644558386663794e4c6c6d65, 0, 0, 137, 0, 1, 0, 100, 6, 0, NULL, NULL, ''),
(13, 'Maxime6666', 'Maxime6666', 'Maxime6666', 0x24327924313024776a6673374e4a44345a434d4d6e4b355259764873654b5a644c344543704d414c45624f72616e73334573686b4c646d3071716a69, 20, 0, 554, 0, 1, 0, 31, 0, 5, NULL, NULL, ''),
(14, 'Maxime1212121', 'Maxime1212121', 'Maxime1212121', 0x243279243130244c543668716851364265684378726c63652e4a674e6550496d634c65496e7146724e704f78707a487a70704f547233565641307379, 100, 100, 100, 0, 0, 0, 100, 0, 0, NULL, NULL, ''),
(15, 'Maxime12121', 'Maxime12121', 'Maxime12121', 0x243279243130246750553378714a45792f583357455a3756636e4f39657551655a4b37353132396e7a53624a723557796a66786a6e566648536d4a36, 0, 0, 111, 0, 0, 0, 100, 0, 0, NULL, NULL, ''),
(18, 'Maximeeeeee', 'Maxime', 'Maxime', 0x24327924313024304b6e76786d4d7876357a5a546b4d43336833504e65743279684e6f4a3037673855334f6536445a4779644c725767625447343961, 100, 100, 60, 0, 0, 0, 88, 0, 1, NULL, NULL, ''),
(19, 'MaximePlay', 'MaximePlay', 'MaximePlay', 0x243279243130246a6d466670746d7a6b514a314c4172434f522e43796536507139484f4d43475137366a55504d536162303131494b4b475361385343, 100, 100, 250, 0, 1, 0, 100, 5, 5, NULL, NULL, ''),
(20, 'Bob', 'Bob', 'Bob', 0x243279243130246952444a4d317a4959665059556762586f7646785465724c56566552547634486e2f6453504c6978685773387a496b736e724d492e, 50, 0, 360, 0, 1, 0, 94, 12, 12, NULL, NULL, ''),
(21, 't', 't', 't', 0x2432792431302435736d46507139522f5667723658456f364f5366397535734761315339732e37365642552f6d57523467335770666a565531686932, 110, 100, 30, 0, 0, 0, 0, 2, 3, NULL, NULL, ''),
(23, 'tt', 'tt', 'tt', 0x243279243130247a715932535270784f41576f3278465939696e5a34754353387a35485a63354431576d726f5355616d7361332f7459736247556f79, 100, 100, 120, 0, 0, 0, 0, 1, 2, NULL, NULL, ''),
(24, 'mdr', 'test', 'test', 0x24327924313024364e31637a31784c36617537583772384165304a59655734496d31585a69654b6f7252774e313235586e3739646133673053505971, 100, 100, 110, 0, 0, 0, 100, 0, 0, NULL, NULL, ''),
(25, 'MaximePro', 'MaximePro', 'MaximePro', 0x243279243130243867477275436a6756464775574d54564470313937755a676a692e55775544444134684b787332694b587263736f6b507864454e57, 120, 150, 210, 0, 1, 0, 81, 0, 5, NULL, NULL, ''),
(26, 'BlackHonder', 'BlackHonder', 'BlackHonder', 0x243279243130245747437351794a5565654d537043746d47486647336541534d4365306f5958382f7076674e36354f6a49696d6e4572575551503457, 110, 130, 250, 0, 1, 0, 84, 2, 5, NULL, NULL, ''),
(27, 'Abdou', 'Lamr', 'Abdou', 0x24327924313024764938787039354d687441796b336a526f616b616e756435437637566e6e54444f4f45785a6e676434565a30327245764a73717465, 10, 0, 72, 0, 0, 0, 88, 0, 0, NULL, NULL, ''),
(39, 'MaximeEmailTest', 'MaximeEmailTest', 'MaximeEmailTest', 0x2432792431302458486b6e2e4b3637555261697632474d426653376b4f7859376c573849386173664c6f726b6c4b556a6237357971566c4431423732, 110, 180, 130, 0, 0, 0, 97, 1, 1, NULL, '734c21fa76f47a5e57204f594b88580a', '12rixe1@gmail.com'),
(44, 'MaximeTestEZ', 'MaximeTestEZ', 'MaximeTestEZ', 0x243279243130246252646f4e6e58376259707457647055525571314565757269756c35687570346f67554366492f6d656178456379644139582f3065, 110, 110, 110, 0, 0, 0, 100, 0, 0, NULL, NULL, '12rixe1@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `Marche`
--

CREATE TABLE `Marche` (
  `IdJoueur` int(11) NOT NULL,
  `IdItem` int(11) NOT NULL,
  `Quantite` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Marche`
--

INSERT INTO `Marche` (`IdJoueur`, `IdItem`, `Quantite`) VALUES
(1, 1, 10),
(1, 4, 1),
(1, 5, 1),
(1, 6, 2),
(1, 8, 2),
(2, 2, 1),
(3, 3, 1);

-- --------------------------------------------------------

--
-- Structure de la table `Potions`
--

CREATE TABLE `Potions` (
  `IdItem` int(11) NOT NULL,
  `Effet` varchar(45) NOT NULL,
  `Duree` int(11) NOT NULL,
  `Soins` int(11) NOT NULL DEFAULT 0
) ;

--
-- Déchargement des données de la table `Potions`
--

INSERT INTO `Potions` (`IdItem`, `Effet`, `Duree`, `Soins`) VALUES
(3, 'Soin', 1, 5),
(4, 'Glace', 1, 0),
(5, 'Feu', 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `RequetesCapital`
--

CREATE TABLE `RequetesCapital` (
  `IdRequete` int(11) NOT NULL,
  `IdJoueur` int(11) DEFAULT NULL,
  `NiveauAtteint` int(11) DEFAULT 0,
  `Statut` varchar(20) DEFAULT 'En attente',
  `DateDemande` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `RequetesCapital`
--

INSERT INTO `RequetesCapital` (`IdRequete`, `IdJoueur`, `NiveauAtteint`, `Statut`, `DateDemande`) VALUES
(1, 4, 1, 'Validé', '2026-05-01 12:36:35'),
(2, 4, 2, 'Validé', '2026-05-01 12:40:10'),
(3, 4, 3, 'Validé', '2026-05-01 12:40:56'),
(4, 2, 1, 'Validé', '2026-05-01 12:42:52'),
(5, 2, 3, 'Validé', '2026-05-01 12:43:13'),
(6, 1, 1, 'Validé', '2026-05-01 12:44:03'),
(7, 1, 2, 'Validé', '2026-05-01 12:44:12'),
(8, 1, 3, 'Validé', '2026-05-01 12:44:20'),
(9, 9, 1, 'Rejeté', '2026-05-01 13:05:58'),
(10, 24, 1, 'Validé', '2026-05-01 13:35:49'),
(11, 39, 1, 'Validé', '2026-05-08 12:56:07'),
(12, 39, 2, 'Validé', '2026-05-08 12:56:25'),
(13, 40, 1, 'Validé', '2026-05-08 12:57:58'),
(14, 40, 2, 'Validé', '2026-05-08 12:58:07'),
(15, 41, 1, 'Validé', '2026-05-08 13:05:46'),
(16, 41, 2, 'Validé', '2026-05-08 13:06:12'),
(17, 42, 1, 'Validé', '2026-05-08 13:11:03'),
(18, 42, 2, 'Validé', '2026-05-08 13:11:13'),
(19, 43, 1, 'Validé', '2026-05-08 13:12:21'),
(20, 43, 2, 'Validé', '2026-05-08 13:12:38'),
(21, 43, 3, 'Validé', '2026-05-08 13:12:48'),
(22, 44, 1, 'Rejeté', '2026-05-08 13:17:05'),
(23, 44, 2, 'Validé', '2026-05-08 13:17:15'),
(24, 44, 3, 'Rejeté', '2026-05-08 13:19:39'),
(25, 9, 1, 'Validé', '2026-05-08 13:20:17'),
(26, 9, 2, 'Rejeté', '2026-05-08 13:22:32'),
(27, 9, 2, 'Validé', '2026-05-08 13:23:12'),
(28, 9, 3, 'Validé', '2026-05-08 13:27:55'),
(29, 6, 1, 'Validé', '2026-05-08 13:28:27'),
(30, 6, 2, 'Validé', '2026-05-08 13:28:40'),
(31, 6, 3, 'Rejeté', '2026-05-08 13:28:45'),
(32, 6, 3, 'Validé', '2026-05-08 13:29:01');

-- --------------------------------------------------------

--
-- Structure de la table `Sorts`
--

CREATE TABLE `Sorts` (
  `IdItem` int(11) NOT NULL,
  `Instantane` tinyint(1) NOT NULL,
  `PointDeDegat` int(11) NOT NULL,
  `Soins` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Sorts`
--

INSERT INTO `Sorts` (`IdItem`, `Instantane`, `PointDeDegat`, `Soins`) VALUES
(6, 1, 0, 5);

-- --------------------------------------------------------

--
-- Structure de la table `Ticket`
--

CREATE TABLE `Ticket` (
  `IdTicket` int(11) NOT NULL,
  `Demande` varchar(300) NOT NULL,
  `IdJoueur` int(11) NOT NULL,
  `EstDemandeArgent` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `Achats`
--
ALTER TABLE `Achats`
  ADD PRIMARY KEY (`IdJoueur`,`IdItem`),
  ADD KEY `fk_IdItem_Achat` (`IdItem`);

--
-- Index pour la table `Armes`
--
ALTER TABLE `Armes`
  ADD PRIMARY KEY (`IdItem`);

--
-- Index pour la table `Armures`
--
ALTER TABLE `Armures`
  ADD PRIMARY KEY (`IdItem`);

--
-- Index pour la table `CategorieEnigme`
--
ALTER TABLE `CategorieEnigme`
  ADD PRIMARY KEY (`IdCategorie`);

--
-- Index pour la table `Enigme`
--
ALTER TABLE `Enigme`
  ADD PRIMARY KEY (`IdEnigme`),
  ADD KEY `fk_IdCategorie` (`IdCategorie`);

--
-- Index pour la table `EssaieEnigmes`
--
ALTER TABLE `EssaieEnigmes`
  ADD PRIMARY KEY (`IdEssaie`),
  ADD KEY `fk_IdEnigme_idx` (`IdEnigme`),
  ADD KEY `fk_IdJoueur_EssaieEnigmes` (`IdJoueur`);

--
-- Index pour la table `Evaluations`
--
ALTER TABLE `Evaluations`
  ADD PRIMARY KEY (`IdJoueur`,`IdItem`),
  ADD KEY `fk_IdItem_Evaluations` (`IdItem`);

--
-- Index pour la table `HistoriqueCapital`
--
ALTER TABLE `HistoriqueCapital`
  ADD PRIMARY KEY (`IdHistorique`);

--
-- Index pour la table `Inventaires`
--
ALTER TABLE `Inventaires`
  ADD PRIMARY KEY (`IdJoueur`,`IdItem`),
  ADD KEY `fk_IdItem_Inventaires` (`IdItem`);

--
-- Index pour la table `Items`
--
ALTER TABLE `Items`
  ADD PRIMARY KEY (`IdItem`);

--
-- Index pour la table `Joueurs`
--
ALTER TABLE `Joueurs`
  ADD PRIMARY KEY (`IdJoueur`),
  ADD UNIQUE KEY `Alias` (`Alias`);

--
-- Index pour la table `Marche`
--
ALTER TABLE `Marche`
  ADD PRIMARY KEY (`IdJoueur`,`IdItem`),
  ADD KEY `fk_IdItem_Marche` (`IdItem`);

--
-- Index pour la table `Potions`
--
ALTER TABLE `Potions`
  ADD PRIMARY KEY (`IdItem`);

--
-- Index pour la table `RequetesCapital`
--
ALTER TABLE `RequetesCapital`
  ADD PRIMARY KEY (`IdRequete`);

--
-- Index pour la table `Sorts`
--
ALTER TABLE `Sorts`
  ADD PRIMARY KEY (`IdItem`);

--
-- Index pour la table `Ticket`
--
ALTER TABLE `Ticket`
  ADD PRIMARY KEY (`IdTicket`),
  ADD KEY `fk_Ticket_Joueur` (`IdJoueur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `CategorieEnigme`
--
ALTER TABLE `CategorieEnigme`
  MODIFY `IdCategorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `Enigme`
--
ALTER TABLE `Enigme`
  MODIFY `IdEnigme` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT pour la table `EssaieEnigmes`
--
ALTER TABLE `EssaieEnigmes`
  MODIFY `IdEssaie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

--
-- AUTO_INCREMENT pour la table `HistoriqueCapital`
--
ALTER TABLE `HistoriqueCapital`
  MODIFY `IdHistorique` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pour la table `Items`
--
ALTER TABLE `Items`
  MODIFY `IdItem` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Joueurs`
--
ALTER TABLE `Joueurs`
  MODIFY `IdJoueur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT pour la table `RequetesCapital`
--
ALTER TABLE `RequetesCapital`
  MODIFY `IdRequete` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT pour la table `Ticket`
--
ALTER TABLE `Ticket`
  MODIFY `IdTicket` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `Achats`
--
ALTER TABLE `Achats`
  ADD CONSTRAINT `fk_IdItem_Achat` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`),
  ADD CONSTRAINT `fk_IdJoueur_Achat` FOREIGN KEY (`IdJoueur`) REFERENCES `Joueurs` (`IdJoueur`);

--
-- Contraintes pour la table `Armes`
--
ALTER TABLE `Armes`
  ADD CONSTRAINT `fk_IdItem_Armes` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`);

--
-- Contraintes pour la table `Armures`
--
ALTER TABLE `Armures`
  ADD CONSTRAINT `fk_IdItem_IdArmures` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`);

--
-- Contraintes pour la table `Enigme`
--
ALTER TABLE `Enigme`
  ADD CONSTRAINT `fk_IdCategorie` FOREIGN KEY (`IdCategorie`) REFERENCES `CategorieEnigme` (`IdCategorie`);

--
-- Contraintes pour la table `EssaieEnigmes`
--
ALTER TABLE `EssaieEnigmes`
  ADD CONSTRAINT `fk_IdEnigme_EssaieEnigmes` FOREIGN KEY (`IdEnigme`) REFERENCES `Enigme` (`IdEnigme`),
  ADD CONSTRAINT `fk_IdJoueur_EssaieEnigmes` FOREIGN KEY (`IdJoueur`) REFERENCES `Joueurs` (`IdJoueur`);

--
-- Contraintes pour la table `Evaluations`
--
ALTER TABLE `Evaluations`
  ADD CONSTRAINT `fk_IdItem_Evaluations` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`),
  ADD CONSTRAINT `fk_IdJoueur_Evaluations` FOREIGN KEY (`IdJoueur`) REFERENCES `Joueurs` (`IdJoueur`);

--
-- Contraintes pour la table `Inventaires`
--
ALTER TABLE `Inventaires`
  ADD CONSTRAINT `fk_IdItem_Inventaires` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`),
  ADD CONSTRAINT `fk_IdJoueur_Inventaires` FOREIGN KEY (`IdJoueur`) REFERENCES `Joueurs` (`IdJoueur`);

--
-- Contraintes pour la table `Marche`
--
ALTER TABLE `Marche`
  ADD CONSTRAINT `fk_IdItem_Marche` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`),
  ADD CONSTRAINT `fk_IdJoueur_Marche` FOREIGN KEY (`IdJoueur`) REFERENCES `Joueurs` (`IdJoueur`);

--
-- Contraintes pour la table `Potions`
--
ALTER TABLE `Potions`
  ADD CONSTRAINT `Fk_IdItem` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`);

--
-- Contraintes pour la table `Sorts`
--
ALTER TABLE `Sorts`
  ADD CONSTRAINT `fk_IdItem_Sorts` FOREIGN KEY (`IdItem`) REFERENCES `Items` (`IdItem`);

--
-- Contraintes pour la table `Ticket`
--
ALTER TABLE `Ticket`
  ADD CONSTRAINT `fk_Ticket_Joueur` FOREIGN KEY (`IdJoueur`) REFERENCES `Joueurs` (`IdJoueur`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
