<?php 
    namespace Mysql
    class Users extends MySQL {
        //These series of statements installs the basic MySQL structure (or calls on classes that help build that out). Basically any table that didn't entirely justify its own 
        private function installSQL(){
            //Install the bare minimum of CourseInfo (which doesn't include modular form items or initial setup)
            $sqlQuery = [
                "CREATE TABLE IF NOT EXISTS `CourseInfo` (",
                    "`CourseID` int(10) NOT NULL auto_increment,",
                    "`CreatedDate` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,",
                    "`Creator`",
                    "PRIMARY KEY USING BTREE (`CourseID`)",
                    "CONSTRAINT FK_Creator FOREIGN KEY (`Creator`)",
                    "REFERENCES Users(UserID)",
                ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT DEFAULT CHARSET=utf8;",
            ];
        }
    }
?>