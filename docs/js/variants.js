/**
 * variants.js — the whole demo, pointed at a different industry.
 *
 * One engine, four datasets. Everything a specific industry changes lives
 * here: the words on screen, the status pipeline labels, the money format
 * and which seed file to load. No module hardcodes "repair" or "client".
 *
 * A variant page sets window.DEMO_VARIANT before loading this file; the
 * landing page at the site root links to each one.
 */
'use strict';

const VARIANTS = {

    /* ── The original: electronics / computer repair ──────────────────────── */
    repair: {
        id: 'repair',
        name: 'Repair Shop',
        industry: 'Electronics & computer repair',
        blurb: 'Laptops, phones and printers through intake, diagnosis, repair and collection.',
        icon: 'wrench',
        company: 'Softorio Repair Centre',
        seed: 'seed-repair.json',
        labels: {
            clientOne: 'Client',        clientMany: 'Clients',
            jobOne: 'Repair',           jobMany: 'Repairs',       jobLower: 'repair',
            jobNew: 'New Repair',       jobSheet: 'Repair Job Sheet',
            jobIntake: 'Log a device that just came in.',
            itemLabel: 'Device',        itemField: 'Device model',
            itemPlaceholder: 'e.g. Dell Latitude 5420',
            serialLabel: 'Serial number',
            problemLabel: 'Reported problem',
            problemHint: 'What did the customer say is wrong?',
            diagnosisLabel: 'Technician diagnosis',
            workLabel: 'Work carried out',
            staffOne: 'Technician',     staffMany: 'Technicians',
            dwellLabel: 'days in lab',
            queueLabel: 'Ready for pickup',
            estimateLabel: 'Estimate',
            specialisation: 'e.g. Laptop & Motherboard',
            catalogueOne: 'Part or Service', catalogueMany: 'Parts & Services',
            catalogueExample: 'e.g. Laptop Battery (OEM)',
            saleOne: 'Sale', saleMany: 'Sales', saleNew: 'New Sale',
        },
        statuses: {
            in_progress: 'In Progress',       on_hold: 'On Hold',
            waiting_for_parts: 'Waiting for Parts',
            ready_for_pickup: 'Ready for Pickup',
            completed: 'Completed', collected: 'Collected', cancelled: 'Cancelled',
        },
    },

    /* ── Dental clinic ───────────────────────────────────────────────────── */
    dental: {
        id: 'dental',
        name: 'Dental Clinic',
        industry: 'Dental practice management',
        blurb: 'Patients, treatment plans, lab work and billing across a multi-chair clinic.',
        icon: 'tooth',
        company: 'Dhaka Smile Dental Care',
        seed: 'seed-dental.json',
        labels: {
            clientOne: 'Patient',       clientMany: 'Patients',
            jobOne: 'Treatment',        jobMany: 'Treatments',    jobLower: 'treatment',
            jobNew: 'New Treatment',    jobSheet: 'Treatment Record',
            jobIntake: 'Open a treatment record for a patient.',
            itemLabel: 'Procedure',     itemField: 'Procedure',
            itemPlaceholder: 'e.g. Root Canal — Upper Molar',
            serialLabel: 'Tooth / quadrant',
            problemLabel: 'Presenting complaint',
            problemHint: 'What is the patient reporting?',
            diagnosisLabel: 'Clinical diagnosis',
            workLabel: 'Treatment performed',
            staffOne: 'Dentist',        staffMany: 'Dentists',
            dwellLabel: 'days open',
            queueLabel: 'Ready for review',
            estimateLabel: 'Quoted fee',
            specialisation: 'e.g. Orthodontics',
            catalogueOne: 'Procedure or Material', catalogueMany: 'Procedures & Materials',
            catalogueExample: 'e.g. Zirconia Crown',
            saleOne: 'Sale', saleMany: 'Counter Sales', saleNew: 'New Sale',
        },
        statuses: {
            in_progress: 'In Treatment',      on_hold: 'On Hold',
            waiting_for_parts: 'Waiting for Lab',
            ready_for_pickup: 'Ready for Review',
            completed: 'Treatment Complete', collected: 'Discharged', cancelled: 'Cancelled',
        },
    },

    /* ── Car servicing ───────────────────────────────────────────────────── */
    auto: {
        id: 'auto',
        name: 'Auto Service',
        industry: 'Vehicle servicing & workshop',
        blurb: 'Vehicles through booking, inspection, parts, repair and handover.',
        icon: 'car',
        company: 'Gulshan Auto Workshop',
        seed: 'seed-auto.json',
        labels: {
            clientOne: 'Customer',      clientMany: 'Customers',
            jobOne: 'Job',              jobMany: 'Jobs',          jobLower: 'job',
            jobNew: 'New Job Card',     jobSheet: 'Workshop Job Card',
            jobIntake: 'Book a vehicle into the workshop.',
            itemLabel: 'Vehicle',       itemField: 'Vehicle / model',
            itemPlaceholder: 'e.g. Toyota Axio 2015',
            serialLabel: 'Registration no.',
            problemLabel: 'Reported fault',
            problemHint: 'What did the customer report?',
            diagnosisLabel: 'Workshop diagnosis',
            workLabel: 'Work carried out',
            staffOne: 'Mechanic',       staffMany: 'Mechanics',
            dwellLabel: 'days in bay',
            queueLabel: 'Ready for handover',
            estimateLabel: 'Estimate',
            specialisation: 'e.g. Engine & Transmission',
            catalogueOne: 'Part or Labour', catalogueMany: 'Parts & Labour',
            catalogueExample: 'e.g. Front Brake Pad Set',
            saleOne: 'Sale', saleMany: 'Parts Sales', saleNew: 'New Parts Sale',
        },
        statuses: {
            in_progress: 'In Workshop',       on_hold: 'On Hold',
            waiting_for_parts: 'Awaiting Parts',
            ready_for_pickup: 'Ready for Handover',
            completed: 'Work Complete', collected: 'Delivered', cancelled: 'Cancelled',
        },
    },

    /* ── Appliance / AC servicing (on-site work) ──────────────────────────── */
    ac: {
        id: 'ac',
        name: 'AC & Appliance',
        industry: 'Air-conditioning & appliance service',
        blurb: 'On-site servicing and installation, from call-out to warranty follow-up.',
        icon: 'snowflake',
        company: 'CoolCare Services BD',
        seed: 'seed-ac.json',
        labels: {
            clientOne: 'Client',        clientMany: 'Clients',
            jobOne: 'Service Call',     jobMany: 'Service Calls', jobLower: 'service call',
            jobNew: 'New Service Call', jobSheet: 'Service Report',
            jobIntake: 'Log a service call or installation request.',
            itemLabel: 'Unit',          itemField: 'Unit / model',
            itemPlaceholder: 'e.g. Gree 1.5 Ton Split AC',
            serialLabel: 'Serial / asset tag',
            problemLabel: 'Reported issue',
            problemHint: 'What is the client reporting?',
            diagnosisLabel: 'Engineer diagnosis',
            workLabel: 'Service performed',
            staffOne: 'Engineer',       staffMany: 'Engineers',
            dwellLabel: 'days open',
            queueLabel: 'Ready to close',
            estimateLabel: 'Quoted amount',
            specialisation: 'e.g. Split & VRF Systems',
            catalogueOne: 'Part or Service', catalogueMany: 'Parts & Services',
            catalogueExample: 'e.g. Run Capacitor',
            saleOne: 'Sale', saleMany: 'Counter Sales', saleNew: 'New Sale',
        },
        statuses: {
            in_progress: 'In Progress',       on_hold: 'On Hold',
            waiting_for_parts: 'Awaiting Parts',
            ready_for_pickup: 'Ready to Close',
            completed: 'Work Complete', collected: 'Closed', cancelled: 'Cancelled',
        },
    },
};

/** The variant this page runs. Defaults to the original repair shop. */
const VARIANT = VARIANTS[window.DEMO_VARIANT] ?? VARIANTS.repair;

/** Shorthand used throughout the modules: L.jobMany, L.clientOne, … */
const L = VARIANT.labels;
