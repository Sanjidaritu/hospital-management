const express = require("express");
const cors = require("cors");
const mysql = require("mysql2/promise");

const app = express();

app.use(cors());
app.use(express.json());
app.use(express.static(__dirname));

/* =====================================================
   RAILWAY MYSQL
===================================================== */

const db = mysql.createPool({
  host: process.env.MYSQLHOST,
  port: process.env.MYSQLPORT || 3306,
  user: process.env.MYSQLUSER,
  password: process.env.MYSQLPASSWORD,
  database: process.env.MYSQLDATABASE,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});


/* =====================================================
   HOME
===================================================== */

app.get("/", (req, res) => {
  res.json({
    success: true,
    message: "Hospital Management API is running"
  });
});


/* =====================================================
   TEST DATABASE
===================================================== */

app.get("/api/test-db", async (req, res) => {
  try {

    const [rows] = await db.query(
      "SELECT 1 AS connected"
    );

    res.json({
      success: true,
      message: "Database connected successfully",
      data: rows
    });

  } catch (error) {

    console.error("Database error:", error);

    res.status(500).json({
      success: false,
      message: "Database connection failed",
      error: error.message
    });

  }
});


/* =====================================================
   GET INSURANCE PLANS
===================================================== */

app.get("/api/insurance", async (req, res) => {

  try {

    const [rows] = await db.query(`
      SELECT
        id,
        name
      FROM insurance_plans
      ORDER BY name
    `);

    res.json(rows);

  } catch (error) {

    console.error("Insurance error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to fetch insurance plans",
      error: error.message
    });

  }

});


/* =====================================================
   GET LANGUAGES
===================================================== */

app.get("/api/languages", async (req, res) => {

  try {

    const [rows] = await db.query(`
      SELECT
        id,
        name
      FROM languages
      ORDER BY name
    `);

    res.json(rows);

  } catch (error) {

    console.error("Languages error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to fetch languages",
      error: error.message
    });

  }

});


/* =====================================================
   GET SPECIALTIES
===================================================== */

app.get("/api/specialties", async (req, res) => {

  try {

    const [rows] = await db.query(`
      SELECT
        id,
        name
      FROM specialties
      ORDER BY name
    `);

    res.json(rows);

  } catch (error) {

    console.error("Specialties error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to fetch specialties",
      error: error.message
    });

  }

});


/* =====================================================
   GET AREAS
===================================================== */

app.get("/api/areas", async (req, res) => {

  try {

    const [rows] = await db.query(`
      SELECT
        id,
        name
      FROM areas
      ORDER BY name
    `);

    res.json(rows);

  } catch (error) {

    console.error("Areas error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to fetch areas",
      error: error.message
    });

  }

});


/* =====================================================
   GET PROVIDERS
===================================================== */

app.get("/api/providers", async (req, res) => {

  try {

    const {
      keyword,
      insurance,
      language,
      gender,
      specialty,
      area,
      sort
    } = req.query;


    let sql = `
      SELECT DISTINCT
        p.id,
        p.name,
        p.gender,
        s.name AS specialty,
        a.name AS area

      FROM providers p

      LEFT JOIN specialties s
        ON p.specialty_id = s.id

      LEFT JOIN areas a
        ON p.area_id = a.id

      LEFT JOIN provider_languages pl
        ON p.id = pl.provider_id

      LEFT JOIN languages l
        ON pl.language_id = l.id

      LEFT JOIN provider_insurance pi
        ON p.id = pi.provider_id

      LEFT JOIN insurance_plans i
        ON pi.insurance_id = i.id

      WHERE 1 = 1
    `;


    const params = [];


    /* KEYWORD */

    if (keyword) {

      sql += `
        AND (
          p.name LIKE ?
          OR s.name LIKE ?
          OR a.name LIKE ?
        )
      `;

      const search = `%${keyword}%`;

      params.push(
        search,
        search,
        search
      );

    }


    /* GENDER */

    if (gender) {

      sql += `
        AND p.gender = ?
      `;

      params.push(gender);

    }


    /* SPECIALTY */

    if (specialty) {

      sql += `
        AND s.name = ?
      `;

      params.push(specialty);

    }


    /* AREA */

    if (area) {

      sql += `
        AND a.name = ?
      `;

      params.push(area);

    }


    /* LANGUAGE */

    if (language) {

      sql += `
        AND l.name = ?
      `;

      params.push(language);

    }


    /* INSURANCE */

    if (insurance) {

      sql += `
        AND i.name = ?
      `;

      params.push(insurance);

    }


    /* SORT */

    if (sort === "name_asc") {

      sql += `
        ORDER BY p.name ASC
      `;

    } else if (sort === "name_desc") {

      sql += `
        ORDER BY p.name DESC
      `;

    } else {

      sql += `
        ORDER BY p.id ASC
      `;

    }


    const [providers] =
      await db.query(
        sql,
        params
      );


    res.json({
      success: true,
      providers
    });


  } catch (error) {

    console.error(
      "Provider error:",
      error
    );

    res.status(500).json({
      success: false,
      message: "Failed to fetch providers",
      error: error.message
    });

  }

});


/* =====================================================
   PROVIDER LANGUAGES
===================================================== */

app.get(
  "/api/providers/:id/languages",
  async (req, res) => {

    try {

      const providerId =
        req.params.id;


      const [languages] =
        await db.query(`
          SELECT
            l.id,
            l.name

          FROM provider_languages pl

          JOIN languages l
            ON pl.language_id = l.id

          WHERE pl.provider_id = ?

          ORDER BY l.name
        `, [providerId]);


      res.json({
        success: true,
        languages
      });


    } catch (error) {

      console.error(error);

      res.status(500).json({
        success: false,
        message: "Failed to fetch languages",
        error: error.message
      });

    }

  }
);


/* =====================================================
   PROVIDER INSURANCE
===================================================== */

app.get(
  "/api/providers/:id/insurance",
  async (req, res) => {

    try {

      const providerId =
        req.params.id;


      const [insurance] =
        await db.query(`
          SELECT
            i.id,
            i.name

          FROM provider_insurance pi

          JOIN insurance_plans i
            ON pi.insurance_id = i.id

          WHERE pi.provider_id = ?

          ORDER BY i.name
        `, [providerId]);


      res.json({
        success: true,
        insurance
      });


    } catch (error) {

      console.error(error);

      res.status(500).json({
        success: false,
        message: "Failed to fetch insurance plans",
        error: error.message
      });

    }

  }
);


/* =====================================================
   START SERVER
===================================================== */

const PORT =
  process.env.PORT || 3000;


app.listen(
  PORT,
  "0.0.0.0",
  () => {

    console.log(
      `Server running on port ${PORT}`
    );

  }
);
