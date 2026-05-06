DROP DATABASE mydb;
CREATE DATABASE mydb;
USE mydb;

CREATE TABLE Joueurs (
  IdJoueur INT NOT NULL AUTO_INCREMENT,
  Alias VARCHAR(45) UNIQUE NOT NULL,
  Nom VARCHAR(45) NOT NULL,
  Prenom VARCHAR(45) NOT NULL,
  MDP VARBINARY(128) NOT NULL,
  Mail VARCHAR(254) NOT NULL,
  Guid VARCHAR(64),
  GuidReset VARCHAR(64),
  PieceBronze INT NOT NULL DEFAULT 100,
  PieceArgent INT NOT NULL DEFAULT 100,
  PieceOr INT NOT NULL DEFAULT 100,
  EstAdmin TINYINT(1) NOT NULL DEFAULT 0,
  EstMage TINYINT(1) NOT NULL DEFAULT 0,
  NbDemandeArgent INT NOT NULL DEFAULT 0,
  PV INT NOT NULL DEFAULT 100,
  StreakMagie INT NOT NULL DEFAULT 0,
  MagieReussies int(11) DEFAULT 0,

  PRIMARY KEY (IdJoueur)
);


CREATE TABLE Items (
  IdItem INT NOT NULL AUTO_INCREMENT,
  Nom VARCHAR(45) NOT NULL,
  Type VARCHAR(1) NOT NULL,
  Prix INT NOT NULL,
  Description VARCHAR(300) NOT NULL,
  image VARCHAR(300),
  Rarete int(11) DEFAULT 1,
  CHECK (Type in ('A', 'R', 'P', 'S')),
  PRIMARY KEY (IdItem)
);

CREATE TABLE Armes (
  IdItem INT NOT NULL,
  Efficacite INT NOT NULL,
  Genre VARCHAR(45) NOT NULL,
  PRIMARY KEY (IdItem),
  CONSTRAINT fk_IdItem_Armes
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem)
);

CREATE TABLE Armures (
  IdItem INT NOT NULL,
  Taille VARCHAR(45) NOT NULL,
  Matiere VARCHAR(45) NOT NULL,
  PRIMARY KEY (IdItem),
  CONSTRAINT fk_IdItem_IdArmures
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem)
);

CREATE TABLE Potions (
  IdItem INT NOT NULL,
  Effet VARCHAR(45) NOT NULL,
  Duree INT NOT NULL,
  Soins INT NOT NULL DEFAULT 0,
  CHECK (Soins <= 5),
  PRIMARY KEY (IdItem),
  CONSTRAINT Fk_IdItem
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem)
);

CREATE TABLE Sorts (
  IdItem INT NOT NULL,
  Instantane TINYINT(1) NOT NULL,
  PointDeDegat INT NOT NULL,
  Soins INT NOT NULL DEFAULT 0,
  PRIMARY KEY (IdItem),
  CONSTRAINT fk_IdItem_Sorts
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem)
);

CREATE TABLE Inventaires (
  IdJoueur INT NOT NULL,
  IdItem INT NOT NULL,
  Quantite INT NOT NULL,
  PRIMARY KEY (IdJoueur, IdItem),
  CONSTRAINT fk_IdItem_Inventaires
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem),
  CONSTRAINT fk_IdJoueur_Inventaires
    FOREIGN KEY (IdJoueur)
    REFERENCES Joueurs (IdJoueur)
);

CREATE TABLE Marche (
  IdJoueur INT NOT NULL,
  IdItem INT NOT NULL,
  Quantite INT NOT NULL,
  PRIMARY KEY (IdJoueur, IdItem),
  CONSTRAINT fk_IdItem_Marche
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem),
  CONSTRAINT fk_IdJoueur_Marche
    FOREIGN KEY (IdJoueur)
    REFERENCES Joueurs (IdJoueur)
);

CREATE TABLE Evaluations (
  IdJoueur INT NOT NULL,
  IdItem INT NOT NULL,
  Etoiles INT UNSIGNED NOT NULL,
  Commentaire VARCHAR(1000) NOT NULL,
  PRIMARY KEY (IdJoueur, IdItem),
  CONSTRAINT fk_IdItem_Evaluations
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem),
  CONSTRAINT fk_IdJoueur_Evaluations
    FOREIGN KEY (IdJoueur)
    REFERENCES Joueurs (IdJoueur)
);

CREATE TABLE CategorieEnigme (
  IdCategorie int NOT NULL AUTO_INCREMENT,
  Categorie varchar(45) NOT NULL,
  EstMagie tinyint(1) NOT NULL,
  PRIMARY KEY (IdCategorie)
);

CREATE TABLE Enigme (
  IdEnigme int NOT NULL AUTO_INCREMENT,
  IdCategorie int NOT NULL,
  Difficulte int NOT NULL,
  Question varchar(100) NOT NULL,
  Reponse1 varchar(255) NOT NULL,
  Reponse2 varchar(255) NOT NULL,
  Reponse3 varchar(255) NOT NULL,
  Reponse4 varchar(255) NOT NULL,
  BonneReponse tinyint NOT NULL,
  PRIMARY KEY (IdEnigme),
  CONSTRAINT fk_IdCategorie
    FOREIGN KEY (IdCategorie)
    REFERENCES CategorieEnigme (IdCategorie)
);

CREATE TABLE EssaieEnigmes (
  IdEssaie int NOT NULL AUTO_INCREMENT,
  IdJoueur INT NOT NULL,
  IdEnigme INT NOT NULL,
  Reussi TINYINT(1) NOT NULL,
  PRIMARY KEY (IdEssaie),
  INDEX fk_IdEnigme_idx (IdEnigme ASC) VISIBLE,
  CONSTRAINT fk_IdEnigme_EssaieEnigmes
    FOREIGN KEY (IdEnigme)
    REFERENCES Enigme (IdEnigme),
  CONSTRAINT fk_IdJoueur_EssaieEnigmes
    FOREIGN KEY (IdJoueur)
    REFERENCES Joueurs (IdJoueur)
);

CREATE TABLE Ticket (
  IdTicket INT NOT NULL AUTO_INCREMENT,
  Demande VARCHAR(300) NOT NULL,
  IdJoueur INT NOT NULL,
  EstDemandeArgent TINYINT(1) NOT NULL,
  PRIMARY KEY (IdTicket),
  CONSTRAINT fk_Ticket_Joueur
    FOREIGN KEY (IdJoueur)
    REFERENCES Joueurs (IdJoueur)
);

CREATE TABLE Achats (
  IdJoueur INT NOT NULL,
  IdItem INT NOT NULL,
  PRIMARY KEY (IdJoueur, IdItem),
  CONSTRAINT fk_IdJoueur_Achat
    FOREIGN KEY (IdJoueur)
    REFERENCES Joueurs (IdJoueur),
  CONSTRAINT fk_IdItem_Achat
    FOREIGN KEY (IdItem)
    REFERENCES Items (IdItem)
);

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
  StreakMagie
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

INSERT INTO CategorieEnigme (Categorie, EstMagie) VALUES
  ('Culture Générale', 0),
  ('Magie', 1);

INSERT INTO Enigme (IdCategorie, Difficulte, Question, Reponse1, Reponse2, Reponse3, Reponse4, BonneReponse) VALUES
  (1, 1, 'Quelle est la capitale de la France ?', 'Lyon', 'Paris', 'Marseille', 'Bordeaux', 2),
  (1, 2, 'Moyen', 'Lyon', 'Paris', 'Marseille', 'Bordeaux', 2),
  (2, 3, 'Enigme magie', '1', '2', '3', '4', 2),
  (2,3,'Quel est la masse du soleil','1.9885 × 10³⁰ kilograms', '10cm','100kg', '1000kg', 1), 
  (2,3,'Quel est le sort de base du mage','Boule de feu','Boule de glace','Boule de foudre','Boule de terre', 1),
  (1,1,'Quelle est la couleur du ciel par temps clair ?','Bleu','Vert','Rouge','Jaune',1),
  (1,1,'Combien y a-t-il de jours dans une semaine ?','5','6','7','8',3),
  (1,1,'Quel animal miaule ?','Chien','Chat','Oiseau','Poisson',2),
(1,1,'Quelle est la première lettre de l’alphabet ?','A','B','C','D',1),
(1,1,'Quel fruit est jaune ?','Pomme','Banane','Fraise','Raisin',2),

(1,2,'Quel est le plus grand océan du monde ?','Atlantique','Indien','Arctique','Pacifique',4),
(1,2,'Combien font 5 x 6 ?','11','30','25','35',2),
(1,2,'Quel gaz respirons-nous principalement ?','Oxygène','Hydrogène','Azote','CO2',3),
(1,2,'Quel est le continent de l’Égypte ?','Asie','Europe','Afrique','Amérique',3),
(1,2,'Quel instrument a des touches noires et blanches ?','Guitare','Piano','Violon','Flûte',2),

(2,1,'Je suis rond et je donne de la lumière la nuit, qui suis-je ?','Le soleil','La lune','Une étoile','Une lampe',2),
(2,1,'Plus je sèche, plus je suis mouillé. Qui suis-je ?','Éponge','Serviette','Nuage','Pluie',2),
(2,1,'Je tombe sans me faire mal, qui suis-je ?','Pluie','Feuille','Neige','Vent',3),
(2,1,'Je peux être cassé sans être touché. Qui suis-je ?','Secret','Verre','Bois','Pierre',1),
(2,1,'Je monte mais ne descends jamais. Qui suis-je ?','Âge','Escalier','Ballon','Température',1),

(2,2,'Je parle sans bouche et j’entends sans oreilles. Qui suis-je ?','Téléphone','Écho','Radio','Vent',2),
(2,2,'Plus j’ai de gardiens, moins je suis en sécurité. Qui suis-je ?','Secret','Trésor','Maison','Ville',1),
(2,2,'Je suis toujours devant toi mais tu ne peux pas me voir. Qui suis-je ?','Futur','Air','Ombre','Mur',1),
(2,2,'Je peux remplir une pièce sans prendre de place. Qui suis-je ?','Air','Lumière','Son','Odeur',2),
(2,2,'Qu’est-ce qui a des clés mais n’ouvre pas de portes ?','Clavier','Carte','Serrure','Coffre',1);

CALL CreateArme('Épée Magique', 300, 'Une épee magique', 'epee.png', 1, 'Deux mains');
CALL CreateArmure('Armure En Fer', 150, 'Une grosse armure capable de vous protéger contre les attaques!', 'amure1.png', 'Grande', 'Fer');
CALL CreatePotion('Potion Magique de Soin', 34, 'Une potion de soin très utiles en combat!', 'soin1.png', 'Soin', 1, 5);
CALL CreatePotion('Potion Magique De Glace', 26, 'Gèle les ennemies de toute tailles!', 'potion2.png', 'Glace', 1, 0);
CALL CreatePotion('Potion Magique De Feu', 39, 'Attention sa brule!', 'potion3.png', 'Feu', 1, 0);
CALL CreateSort('Sort de Soins', 100, 'aaaaaaaaaaaaaahhhhh', 'heal.webp', 1, 0, 5);

INSERT INTO Joueurs (Alias, Nom, Prenom, MDP, EstAdmin, Mail, Guid) VALUES
  ('Trosfros', 'Guichard', 'Maxime', 0x243279243130246a6a667838374f3069666748465251715932685a4f65724f55754b6c6f43644c4f506f38645079317576506a34714b633277304943, 1, 'master@app.net', NULL),
  ('Frou_Frou', 'Perron', 'Gabriel', 0x243279243130246b446647385a32445654434f6c5054616959416331652e6e4d555a7a526376684a616641795837504f58724b51454b63624e4c394f, 1, 'master@app.net', NULL),
  ('Lebon', 'Lebon', 'Pascal', 0x24327924313024416d6d4968546832456f47736469437575754e72397569474f6f444f6866444c417259365a4e6b424a334d674f7a6a305a725a6453, 0, 'master@app.net', NULL),
  ('Orisa', 'Orisa', 'Orisa', 0x243279243130244536595a6b454a735873636a566a2e4b5649322e794f6b506955386c4b756e51524c4649326a554a622e5168575062564c7371766d, 0, 'master@app.net', NULL),
  ('fdwefewf', 'bonjour', 'salut', 0x2432792431302461764f446c396e38444c4a3444776d457864546245654a6e746c543043366b47753965724a5a2f444462795857314e652e5043414b, 0, 'master@app.net', NULL),
  ('tamere', 'tamere', 'tamere', 0x243279243130242e4d6c30486b4533617a52746c4d6a6d45572f304b4f397a4439502e366a687956436545597041385a4c732e4c47496c7546526447, 0, 'master@app.net', NULL);

INSERT INTO Marche (IdJoueur, IdItem, Quantite) VALUES
  (1, 1, 15),
  (2, 2, 5),
  (3, 3, 10),
  (4, 4, 13),
  (5, 5, 3),
  (1, 6, 3);

CALL BuyItem(3,4,3);
SELECT * from Joueurs;
SELECT * from Inventaires;
SELECT * FROM Marche;
