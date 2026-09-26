-- ================================================================
-- Political Donation Network Analysis - Version 1 seed data
-- 100% synthetic. Hand-authored (small dataset, no generator needed).
-- Covers all 10 required demonstration cases - see README.
-- ================================================================
USE political_donation_network;
SET FOREIGN_KEY_CHECKS=0;

-- addresses
INSERT INTO addresses (id, address_id, address_text) VALUES
(1,'ADD-001','14 MG Road, Pune'),
(2,'ADD-002','22 Lake View Apartments, Mumbai'),
(3,'ADD-003','7 Residency Road, Bengaluru'),
(4,'ADD-004','19 Church Street, Bengaluru'),
(5,'ADD-005','5 Sector 18, Gurugram'),
(6,'ADD-006','8 Sector 44, Gurugram'),
(7,'ADD-007','11 Civil Lines, Jaipur'),
(8,'ADD-008','28 Anna Nagar, Chennai'),
(9,'ADD-009','40 Nariman Point, Mumbai'),
(10,'ADD-010','3 Industrial Estate, Thane'),
(11,'ADD-011','9 Park Street, Kolkata'),
(12,'ADD-012','Skyline Business Tower, Sector 62, Noida');

-- phone_numbers
INSERT INTO phone_numbers (id, phone_id, phone_number) VALUES
(1,'PH-001','90210-55678'),
(2,'PH-002','98450-11122'),
(3,'PH-003','99110-22334'),
(4,'PH-004','98290-44556'),
(5,'PH-005','98200-11223'),
(6,'PH-006','91234-56789'),
(7,'PH-007','99887-65544'),
(8,'PH-008','90000-11111'),
(9,'PH-009','98765-43210'),
(10,'PH-010','91111-22233');

-- directors
INSERT INTO directors (id, director_id, name) VALUES
(1,'DIR-001','Vikram Singh'),
(2,'DIR-002','Meera Nair'),
(3,'DIR-003','Ashok Verma'),
(4,'DIR-004','Priya Menon'),
(5,'DIR-005','Rakesh Gupta'),
(6,'DIR-006','Sunita Rao');

-- political_parties
INSERT INTO political_parties (id, party_id, party_name) VALUES
(1,'PP-001','Political Party Alpha'),
(2,'PP-002','Political Party Horizon'),
(3,'PP-003','Political Party National'),
(4,'PP-004','Political Party Unity');

-- companies (CASE 7: CMP-002/CMP-003 share director DIR-002; CASE 4/9: CMP-004/CMP-005 share director DIR-003, donate close in time)
INSERT INTO companies (id, entity_id, company_name, phone_id, address_id, director_id) VALUES
(1,'CMP-001','Trident Traders Pvt Ltd',1,10,1),
(2,'CMP-002','Meridian Ventures Pvt Ltd',2,3,2),
(3,'CMP-003','Meridian Holdings Pvt Ltd',2,4,2),
(4,'CMP-004','Orion Distributors Pvt Ltd',3,5,3),
(5,'CMP-005','Vertex Commodities Pvt Ltd',7,6,3),
(6,'CMP-006','Silverline Corporate Services Pvt Ltd',5,9,4);

-- donors
INSERT INTO donors (id, entity_id, name, phone_id, address_id) VALUES
(1,'IND-001','Rahul Mehta',1,2),
(2,'IND-002','Vikram Singh',NULL,2),
(3,'IND-003','R. Kapoor',NULL,1),
(4,'IND-004','A. Kapoor',8,1),
(5,'IND-005','S. Iyer',2,3),
(6,'IND-006','N. Iyer',NULL,4),
(7,'IND-007','P. Nair',7,NULL),
(8,'IND-008','H. Bansal',4,7),
(9,'IND-009','M. Chandran',9,8),
(10,'IND-010','NRI Donor - R. Chandran (Singapore, synthetic demonstration entity)',10,8),
(11,'IND-011','A. Sethi',NULL,12),
(12,'IND-012','B. Gowda',NULL,12),
(13,'IND-013','C. Pillai',NULL,12),
(14,'IND-014','D. Joshi',6,11),
(15,'IND-015','E. Reddy',NULL,11),
(16,'IND-016','F. Kaur',NULL,NULL),
(17,'IND-017','K. Verma',1,NULL),
(18,'IND-018','G. Verma',6,NULL);

-- politicians (added for the landing "Politicians" category - see README)
INSERT INTO politicians (id, entity_id, name, party_id, designation) VALUES
(1,'POL-001','Arjun Deshmukh',1,'Member of Parliament'),
(2,'POL-002','Kavita Iyer',2,'State Legislator'),
(3,'POL-003','Rohan Kulkarni',3,'Party Spokesperson'),
(4,'POL-004','Neha Bhatt',4,'Member of Parliament');

-- donations (donor_type + donor_ref_id points to donors.id or companies.id)
INSERT INTO donations (id, transaction_id, donor_type, donor_ref_id, recipient_party_id, amount, donation_date, description) VALUES
(1,'TX001','donor',1,2,95000,'2024-04-02','Donation'),
(2,'TX002','donor',2,2,110000,'2024-04-06','Donation'),
(3,'TX003','company',1,2,220000,'2024-04-09','Donation'),
(4,'TX004','donor',17,2,150000,'2024-04-13','Donation'),
(5,'TX005','donor',3,1,45000,'2024-02-03','Donation'),
(6,'TX006','donor',4,3,38000,'2024-05-21','Donation'),
(7,'TX007','donor',5,1,60000,'2024-03-04','Donation'),
(8,'TX008','company',2,1,150000,'2024-03-05','Donation'),
(9,'TX009','company',3,1,140000,'2024-03-08','Donation'),
(10,'TX010','donor',6,1,42000,'2024-03-10','Donation'),
(11,'TX011','donor',7,1,30000,'2024-03-11','Donation'),
(12,'TX012','company',4,1,220000,'2024-01-10','Donation'),
(13,'TX013','company',5,1,175000,'2024-01-12','Donation'),
(14,'TX014','donor',8,3,950000,'2024-05-02','Donation'),
(15,'TX015','donor',9,4,90000,'2024-06-05','Donation'),
(16,'TX016','donor',10,4,300000,'2024-06-01','Donation'),
(17,'TX017','donor',11,1,15000,'2024-01-15','Donation'),
(18,'TX018','donor',12,2,22000,'2024-02-20','Donation'),
(19,'TX019','donor',13,3,30000,'2024-03-25','Donation'),
(20,'TX020','donor',14,2,25000,'2024-02-10','Donation'),
(21,'TX021','donor',18,4,28000,'2024-05-18','Donation'),
(22,'TX022','donor',15,1,20000,'2024-04-22','Donation'),
(23,'TX023','donor',16,2,35000,'2024-03-30','Donation'),
(24,'TX024','donor',1,1,20000,'2024-07-01','Donation'),
(25,'TX025','donor',3,1,12000,'2024-08-15','Donation'),
(26,'TX026','company',6,3,260000,'2024-02-20','Donation'),
(27,'TX027','company',6,3,40000,'2024-06-10','Donation'),
(28,'TX028','donor',5,2,15000,'2024-09-01','Donation'),
(29,'TX029','donor',9,1,10000,'2024-08-01','Donation'),
(30,'TX030','donor',16,1,18000,'2024-05-05','Donation');

SET FOREIGN_KEY_CHECKS=1;
