const express = require("express");
const mysql = require("mysql2/promise");
const cors = require("cors");
const path = require("path");

const app = express();

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve HTML/CSS/JS files
app.use(express.static(__dirname));

// MySQL connection
const db = mysql.createPool({
    host: process.env.MYSQLHOST,
    port: process.env.MYSQLPORT,
    user: process.env.MYSQLUSER,
    password: process.env.MYSQLPASSWORD,
    database: process.env.MYSQLDATABASE,
    waitForConnections: true,
    connectionLimit: 10
});

// Test database connection
app.get("/api/test", async (req, res) => {
    try {
        const [rows] = await db.query("SELECT 1 AS connected");

        res.json({
            success: true,
            message: "MySQL connected successfully",
            data: rows
        });

    } catch (error) {
        console.error(error);

        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});


// Get all providers / Search providers
app.get("/api/providers", async (req, res) => {

    try {

        const {
            keyword = "",
            insurance = "",
            language = "",
            specialty = "",
            area = "",
            gender = ""
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

        // Keyword
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

        // Insurance
        if (
            insurance.trim() !== "" &&
            insurance !== "All Insurance Types"
        ) {

            sql += `
                AND ip.name = ?
            `;

            params.push(insurance);
        }

        // Language
        if (
            language.trim() !== "" &&
            language !== "Any Languages"
        ) {

            sql += `
                AND l.name = ?
            `;

            params.push(language);
        }

        // Specialty
        if (
            specialty.trim() !== "" &&
            specialty !== "All Specialties"
        ) {

            sql += `
                AND s.name = ?
            `;

            params.push(specialty);
        }

        // Area
        if (
            area.trim() !== "" &&
            area !== "All Areas"
        ) {

            sql += `
                AND a.name = ?
            `;

            params.push(area);
        }

        // Gender
        if (
            gender.trim() !== "" &&
            gender !== "All"
        ) {

            sql += `
                AND p.gender = ?
            `;

            params.push(gender);
        }

        sql += `
            ORDER BY p.name ASC
        `;

        const [providers] = await db.query(sql, params);

        res.json({
            success: true,
            count: providers.length,
            providers: providers
        });

    } catch (error) {

        console.error("Provider search error:", error);

        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});


// Get languages
app.get("/api/languages", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT id, name
            FROM languages
            ORDER BY name
        `);

        res.json(rows);

    } catch (error) {

        res.status(500).json({
            error: error.message
        });

    }
});


// Get insurance plans
app.get("/api/insurance", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT id, name
            FROM insurance_plans
            ORDER BY name
        `);

        res.json(rows);

    } catch (error) {

        res.status(500).json({
            error: error.message
        });

    }
});


// Get specialties
app.get("/api/specialties", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT id, name
            FROM specialties
            ORDER BY name
        `);

        res.json(rows);

    } catch (error) {

        res.status(500).json({
            error: error.message
        });

    }
});


// Get areas
app.get("/api/areas", async (req, res) => {

    try {

        const [rows] = await db.query(`
            SELECT id, name
            FROM areas
            ORDER BY name
        `);

        res.json(rows);

    } catch (error) {

        res.status(500).json({
            error: error.message
        });

    }
});


// Homepage
app.get("/", (req, res) => {
    res.sendFile(path.join(__dirname, "index.html"));
});


// Find Care page
app.get("/findcare", (req, res) => {
    res.sendFile(path.join(__dirname, "findcare.html"));
});


// Port
const PORT = process.env.PORT || 3000;

app.listen(PORT, "0.0.0.0", () => {
    console.log(`Server running on port ${PORT}`);
});
