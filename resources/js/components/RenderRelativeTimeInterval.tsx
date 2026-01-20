'use client'

import React, { useState, useEffect } from 'react'
import Countdown from './Countdown'
import { Badge } from '@/components/ui/badge'

interface Props {
  startDate?: string | null | undefined
  endDate?: string | null | undefined
}

const RenderRelativeTimeInterval: React.FC<Props> = ({ startDate, endDate }) => {
  const [currentTime, setCurrentTime] = useState<Date>(new Date())
  const [startDateObject, setStartDateObject] = useState<Date | null>(null)
  const [endDateObject, setEndDateObject] = useState<Date | null>(null)
  const [startDateIsValid, setStartDateIsValid] = useState(false)
  const [endDateIsValid, setEndDateIsValid] = useState(false)

  useEffect(() => {
    if (startDate) {
      const startDateObj = new Date(startDate)
      setStartDateObject(startDateObj)
      setStartDateIsValid(!isNaN(startDateObj.getTime()))
    } else {
      setStartDateObject(null)
      setStartDateIsValid(false)
    }

    if (endDate) {
      const endDateObj = new Date(endDate)
      setEndDateObject(endDateObj)
      setEndDateIsValid(!isNaN(endDateObj.getTime()))
    } else {
      setEndDateObject(null)
      setEndDateIsValid(false)
    }
  }, [startDate, endDate])

  useEffect(() => {
    const intervalId = setInterval(() => {
      setCurrentTime(new Date())
    }, 1000)
    return () => clearInterval(intervalId)
  }, [])

  // If no dates are provided, show a dash
  if (!startDate && !endDate) {
    return <span>-</span>
  }

  // If dates are invalid, show invalid message
  if ((startDate && !startDateIsValid) || (endDate && !endDateIsValid)) {
    return <span>Invalid dates</span>
  }

  // Render based on state 
  // (some offers may only have an end date, so handle partial data)
  if (startDateObject && endDateObject && startDateIsValid && endDateIsValid) {
    if (currentTime < startDateObject) {
      return (
        <div className="flex items-center gap-1">
          <Badge variant="outline" className="bg-yellow-100 text-yellow-800 border-yellow-300">
            Starts in
          </Badge>
          <Countdown utcDate={startDate!} />
        </div>
      )
    } else if (currentTime >= startDateObject && currentTime < endDateObject) {
      return (
        <div className="flex items-center gap-1">
          <Badge variant="outline" className="bg-green-100 text-green-800 border-green-300">
            Ends in
          </Badge>
          <Countdown utcDate={endDate!} />
        </div>
      )
    } else {
      return (
        <div className="flex items-center gap-1">
          <Badge variant="destructive">Ended</Badge>
          <Countdown utcDate={endDate!} />
        </div>
      )
    }
  }

  // Handle case where only endDate is provided
  if (endDateObject && endDateIsValid) {
    if (currentTime < endDateObject) {
      return (
        <div className="flex items-center gap-1">
          <Badge variant="outline" className="bg-green-100 text-green-800 border-green-300">
            Ends in
          </Badge>
          <Countdown utcDate={endDate!} />
        </div>
      )
    } else {
      return (
        <div className="flex items-center gap-1">
          <Badge variant="destructive">Ended</Badge>
          <Countdown utcDate={endDate!} />
        </div>
      )
    }
  }

  // Handle case where only startDate is provided
  if (startDateObject && startDateIsValid) {
    if (currentTime < startDateObject) {
      return (
        <div className="flex items-center gap-1">
          <Badge variant="outline" className="bg-yellow-100 text-yellow-800 border-yellow-300">
            Starts in
          </Badge>
          <Countdown utcDate={startDate!} />
        </div>
      )
    } else {
      return (
        <div className="flex items-center gap-1">
          <Badge variant="outline" className="bg-green-100 text-green-800 border-green-300">
            Started
          </Badge>
          <Countdown utcDate={startDate!} />
        </div>
      )
    }
  }

  return <span>-</span>
}

export default RenderRelativeTimeInterval
