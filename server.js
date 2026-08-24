const express = require("express");
const mysql = require("mysql2/promise");
const cors = require("cors");
const path = require("path");

const app = express();

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve HTML, CSS, JavaScript and other static files
app.use(express.static(__dirname));


// ======================================================
// MYSQL CONNECTION
// ======================================================

const db = mysql.createPool({
    host: process.env.MYSQLHOST,
    port: process.env.MYSQLPORT,
    user: process.env.MYSQLUSER,
    password: process.env.MYSQLPASSWORD,
    database: process.env.MYSQLDATABASE,

    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});


// ======================================================
// TEST DATABASE CONNECTION
// ======================================================

app.get("/api/test", async (req, res) => {

    try {

        const [rows] = await db.query(
            "SELECT 1 AS connected"
        );

        res.json({
            success: true,
            message: "MySQL connected successfully",
            data: rows
        });

    } catch (error) {

        console.error("Database connection error:", error);

        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});


// ======================================================
// GET ALL PROVIDERS / SEARCH PROVIDERS
// ======================================================

app.get("/api/providers", async (req, res) => {

    try {

        const {
            keyword = "",
            insurance = "",
            language = "",
            specialty = "",
            area = "",
            gender = "",
            accepting_new_patients = ""
        } = req.query;


        let sql = `
            SELECT DISTINCT

                p.id,

                p.name AS provider,

                s.name AS specialty,

                a.name AS area,

                p.gender

            FROM providers p

            LEFT JOIN specialties s
                ON p.specialty_id = s.id

            LEFT JOIN areas a
                ON p.area_id = a.id

            LEFT JOIN provider_languages pl
                ON p.id = pl.provider_id

            LEFT JOIN languages l
                ON l.id = pl.language_id

            LEFT JOIN provider_insurance pi
                ON p.id = pi.provider_id

            LEFT JOIN insurance_plans ip
                ON ip.id = pi.insurance_id

            WHERE 1 = 1
        `;


        const params = [];


        // ==================================================
        // KEYWORD
        // ==================================================

        if (keyword.trim() !== "") {

            sql += `
                AND (
                    p.name LIKE ?
                    OR s.name LIKE ?
                )
            `;

            params.push(`%${keyword}%`);
            params.push(`%${keyword}%`);
        }


        // ==================================================
        // INSURANCE
        // ==================================================

        if (
            insurance.trim() !== "" &&
            insurance !== "All Insurance Types"
        ) {

            sql += `
                AND ip.name = ?
            `;

            params.push(insurance);
        }


        // ==================================================
        // LANGUAGE
        // ==================================================

        if (
            language.trim() !== "" &&
            language !== "Any Languages"
        ) {

            sql += `
                AND l.name = ?
            `;

            params.push(language);
        }


        // ==================================================
        // SPECIALTY
        // ==================================================

        if (
            specialty.trim() !== "" &&
            specialty !== "All Specialties"
        ) {

            sql += `
                AND s.name = ?
            `;

            params.push(specialty);
        }


        // ==================================================
        // AREA
        // ==================================================

        if (
            area.trim() !== "" &&
            area !== "All Areas"
        ) {

            sql += `
                AND a.name = ?
            `;

            params.push(area);
        }


        // ==================================================
        // GENDER
        // ==================================================

        if (
            gender.trim() !== "" &&
            gender !== "All"
        ) {

            sql += `
                AND p.gender = ?
            `;

            params.push(gender);
        }


        // ==================================================
        // ACCEPTING NEW PATIENTS
        // ==================================================

        if (
            accepting_new_patients !== "" &&
            accepting_new_patients !== "All"
        ) {

            sql += `
                AND p.accepting_new_patients = ?
            `;

            params.push(accepting_new_patients);
        }


        // ==================================================
        // SORT
        // ==================================================

        sql += `
            ORDER BY p.name ASC
        `;


        // ==================================================
        // EXECUTE QUERY
        // ==================================================

        const [providers] = await db.query(
            sql,
            params
        );


        res.json({

            success: true,

            count: providers.length,

            providers: providers

        });


    } catch (error) {

        console.error(
            "Provider search error:",
            error
        );

        res.status(500).json({

            success: false,

            error: error.message

        });

    }

});


// ======================================================
// GET ALL LANGUAGES
// ======================================================

app.get("/api/languages", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT
                id,
                name
            FROM languages
            ORDER BY name ASC
        `);

        res.json(rows);

    } catch (error) {

        console.error(error);

        res.status(500).json({
            error: error.message
        });
    }

});


// ======================================================
// GET ALL INSURANCE PLANS
// ======================================================

app.get("/api/insurance", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT
                id,
                name
            FROM insurance_plans
            ORDER BY name ASC
        `);

        res.json(rows);

    } catch (error) {

        console.error(error);

        res.status(500).json({
            error: error.message
        });
    }

});


// ======================================================
// GET ALL SPECIALTIES
// ======================================================

app.get("/api/specialties", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT
                id,
                name
            FROM specialties
            ORDER BY name ASC
        `);

        res.json(rows);

    } catch (error) {

        console.error(error);

        res.status(500).json({
            error: error.message
        });
    }

});


// ======================================================
// GET ALL AREAS
// ======================================================

app.get("/api/areas", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT
                id,
                name
            FROM areas
            ORDER BY name ASC
        `);

        res.json(rows);

    } catch (error) {

        console.error(error);

        res.status(500).json({
            error: error.message
        });
    }

});


// ======================================================
// HOME PAGE
// ======================================================

app.get("/", (req, res) => {

    res.sendFile(
        path.join(__dirname, "index.html")
    );

});


// ======================================================
// FIND CARE PAGE
// ======================================================

app.get("/findcare", (req, res) => {

    res.sendFile(
        path.join(__dirname, "findcare.html")
    );

});


// ======================================================
// SPECIALIST PAGE
// ======================================================

app.get("/specialist", (req, res) => {

    res.sendFile(
        path.join(__dirname, "Specialist.html")
    );

});


// ======================================================
// START SERVER
// ======================================================

const PORT = process.env.PORT || 3000;

app.listen(
    PORT,
    "0.0.0.0",
    () => {

        console.log(
            `Server running on port ${PORT}`
        );

    }
);
